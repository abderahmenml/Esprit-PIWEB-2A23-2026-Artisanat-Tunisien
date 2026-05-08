<?php

class FaceRecognitionService {
    public function generateFaceId(string $descriptorJson): ?string {
        $descriptor = $this->normalizeDescriptor($descriptorJson);
        if ($descriptor === null) {
            return null;
        }

        $payload = implode('|', array_map(
            static fn (float $value): string => number_format($value, 8, '.', ''),
            $descriptor
        ));

        return 'FACE-' . strtoupper(substr(sha1($payload), 0, 12));
    }

    public function normalizeDescriptor(?string $descriptorJson): ?array {
        if ($descriptorJson === null || trim($descriptorJson) === '') {
            return null;
        }

        $decoded = json_decode($descriptorJson, true);
        if (!is_array($decoded) || count($decoded) !== 128) {
            return null;
        }

        $normalized = [];
        foreach ($decoded as $value) {
            if (!is_numeric($value)) {
                return null;
            }
            $normalized[] = (float) $value;
        }

        return $normalized;
    }

    public function isMatch(string $storedDescriptorJson, string $candidateDescriptorJson, float $threshold): bool {
        $stored = $this->normalizeDescriptor($storedDescriptorJson);
        $candidate = $this->normalizeDescriptor($candidateDescriptorJson);

        if ($stored === null || $candidate === null) {
            return false;
        }

        return $this->distance($stored, $candidate) <= $threshold;
    }

    public function distance(array $a, array $b): float {
        $sum = 0.0;
        $count = min(count($a), count($b));
        for ($i = 0; $i < $count; $i++) {
            $diff = (float) $a[$i] - (float) $b[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }
}
