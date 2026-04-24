<?php
declare(strict_types=1);

final class CvParserService
{
    private string $lastError = '';

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function parseUploadedFile(string $path, string $originalName, string $mime): array
    {
        $this->lastError = '';
        $text = $this->extractText($path, $originalName, $mime);
        $hints = $this->extractHints($text);
        $hints['raw_text'] = $text;
        $hints['source'] = 'upload';
        $hints['parser_error'] = $this->lastError;

        return $hints;
    }

    public function extractText(string $path, string $originalName = '', string $mime = ''): string
    {
        if (!is_file($path) || !is_readable($path)) {
            return '';
        }

        $ext = strtolower(pathinfo($originalName !== '' ? $originalName : $path, PATHINFO_EXTENSION));

        if (in_array($ext, ['txt', 'text'], true) || str_starts_with($mime, 'text/')) {
            return $this->cleanText((string)file_get_contents($path));
        }

        if ($ext === 'docx' || $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            return $this->extractDocxText($path);
        }

        if ($ext === 'pdf' || $mime === 'application/pdf') {
            return $this->extractPdfText($path);
        }

        return '';
    }

    public function extractHints(string $text): array
    {
        $text = $this->cleanText($text);
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R+/', $text) ?: [])));

        $email = $this->matchFirst('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text);
        $phone = $this->matchFirst('/(?:\+?\d[\d\s().-]{7,}\d)/', $text);
        $name = $this->findLabelValue($text, ['name', 'nom', 'full name', 'nom complet']);
        $skills = $this->findSection($text, ['skills', 'competences', 'competences techniques', 'compétences']);
        $experience = $this->findSection($text, ['experience', 'expérience', 'work experience', 'experiences professionnelles', 'expériences professionnelles']);
        $education = $this->findSection($text, ['education', 'formation', 'études', 'etudes']);
        $location = $this->findLabelValue($text, ['location', 'adresse', 'ville', 'city']);
        $title = $this->findLabelValue($text, ['title', 'titre', 'poste', 'profession']);

        if ($name === '') {
            $name = $this->guessName($lines, $email);
        }
        if ($title === '') {
            $title = $this->guessTitle($lines, $name, $email, $phone);
        }

        $skillsList = $this->splitSkills($skills);

        return [
            'candidate_name' => $name,
            'candidate_email' => $email,
            'phone' => $phone,
            'location' => $location,
            'professional_title' => $title,
            'professional_summary' => $this->guessSummary($lines, $name, $title),
            'skills' => implode(', ', $skillsList),
            'experience' => $experience,
            'education' => $education,
            'profile_completeness' => $this->completeness([$name, $email, $phone, $title, $skills, $experience, $education]),
        ];
    }

    private function extractDocxText(string $path): string
    {
        if (!class_exists(ZipArchive::class)) {
            $this->lastError = 'DOCX parsing requires the PHP ZipArchive extension. Enable extension=zip in XAMPP php.ini.';
            return '';
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            $this->lastError = 'The DOCX file could not be opened as a Word document.';
            return '';
        }

        $xml = (string)$zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === '') {
            $this->lastError = 'No readable document.xml text was found inside the DOCX file.';
            return '';
        }

        $xml = preg_replace('/<\/w:p>/', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<\/w:tab>/', "\t", $xml) ?? $xml;

        return $this->cleanText(html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8'));
    }

    private function extractPdfText(string $path): string
    {
        $content = (string)file_get_contents($path);
        if ($content === '') {
            return '';
        }

        $chunks = [];
        if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)\s*Tj/s', $content, $matches)) {
            foreach ($matches[0] as $match) {
                if (preg_match('/\(((?:\\\\.|[^\\\\)])*)\)\s*Tj/s', $match, $m)) {
                    $chunks[] = stripcslashes($m[1]);
                }
            }
        }
        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $content, $matches)) {
            foreach ($matches[1] as $arrayText) {
                if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $arrayText, $parts)) {
                    foreach ($parts[0] as $part) {
                        $chunks[] = stripcslashes(trim($part, '()'));
                    }
                }
            }
        }

        return $this->cleanText(implode("\n", $chunks));
    }

    private function cleanText(string $text): string
    {
        $text = str_replace(["\0", "\r"], ['', "\n"], $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function matchFirst(string $pattern, string $text): string
    {
        return preg_match($pattern, $text, $m) ? trim((string)$m[0]) : '';
    }

    private function findLabelValue(string $text, array $labels): string
    {
        foreach ($labels as $label) {
            $quoted = preg_quote($label, '/');
            if (preg_match('/(?:^|\n)\s*' . $quoted . '\s*[:\-]\s*(.+)/iu', $text, $m)) {
                return trim((string)$m[1]);
            }
        }

        return '';
    }

    private function findSection(string $text, array $labels): string
    {
        $labelsPattern = implode('|', array_map(static fn (string $label): string => preg_quote($label, '/'), $labels));
        $knownHeadings = 'skills|competences|compétences|experience|expérience|education|formation|études|etudes|languages|langues|certifications|projects|projets|summary|profil';

        if (preg_match('/(?:^|\n)\s*(?:' . $labelsPattern . ')\s*[:\-]?\s*(.*?)(?=\n\s*(?:' . $knownHeadings . ')\s*[:\-]|\z)/isu', $text, $m)) {
            return trim((string)$m[1]);
        }

        return '';
    }

    private function splitSkills(string $skills): array
    {
        return array_values(array_filter(array_unique(array_map(
            'trim',
            preg_split('/[,;|•\n]+/u', $skills) ?: []
        ))));
    }

    private function guessName(array $lines, string $email): string
    {
        foreach (array_slice($lines, 0, 5) as $line) {
            if ($email !== '' && str_contains($line, $email)) {
                continue;
            }
            if (preg_match('/\d|@|www\.|linkedin|github/i', $line)) {
                continue;
            }
            $words = preg_split('/\s+/', $line) ?: [];
            if (count($words) >= 2 && count($words) <= 5 && mb_strlen($line) <= 80) {
                return $line;
            }
        }

        return '';
    }

    private function guessTitle(array $lines, string $name, string $email, string $phone): string
    {
        foreach (array_slice($lines, 0, 8) as $line) {
            if ($line === $name || ($email !== '' && str_contains($line, $email)) || ($phone !== '' && str_contains($line, $phone))) {
                continue;
            }
            if (preg_match('/@|\d{4,}|www\.|linkedin|github/i', $line)) {
                continue;
            }
            if (mb_strlen($line) >= 4 && mb_strlen($line) <= 90) {
                return $line;
            }
        }

        return '';
    }

    private function guessSummary(array $lines, string $name, string $title): string
    {
        foreach ($lines as $line) {
            if ($line === $name || $line === $title) {
                continue;
            }
            if (mb_strlen($line) >= 80 && mb_strlen($line) <= 280) {
                return $line;
            }
        }

        return '';
    }

    private function completeness(array $values): int
    {
        $filled = count(array_filter($values, static fn ($value): bool => trim((string)$value) !== ''));
        return (int)round(($filled / max(1, count($values))) * 100);
    }
}
