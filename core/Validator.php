<?php
/**
 * Classe Validator - Validation cote serveur.
 */
class Validator {
    private array $errors = [];

    public function required(string $field, mixed $value, string $label): self {
        if ($value === null || trim((string) $value) === '') {
            $this->errors[$field] = "Le champ \"$label\" est obligatoire.";
        }
        return $this;
    }

    public function email(string $field, string $value, string $label = 'Email'): self {
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "Le champ \"$label\" doit etre une adresse e-mail valide.";
        }
        return $this;
    }

    public function minLength(string $field, string $value, int $min, string $label): self {
        if ($value !== '' && mb_strlen($value) < $min) {
            $this->errors[$field] = "Le champ \"$label\" doit contenir au moins $min caracteres.";
        }
        return $this;
    }

    public function maxLength(string $field, string $value, int $max, string $label): self {
        if ($value !== '' && mb_strlen($value) > $max) {
            $this->errors[$field] = "Le champ \"$label\" ne doit pas depasser $max caracteres.";
        }
        return $this;
    }

    public function matches(string $field, string $value1, string $value2, string $label): self {
        if ($value1 !== $value2) {
            $this->errors[$field] = "Le champ \"$label\" ne correspond pas.";
        }
        return $this;
    }

    public function inList(string $field, string $value, array $list, string $label): self {
        if (!in_array($value, $list, true)) {
            $this->errors[$field] = "Le champ \"$label\" contient une valeur non autorisee.";
        }
        return $this;
    }

    public function alpha(string $field, string $value, string $label): self {
        if ($value !== '' && !preg_match('/^[\p{L}\s\-]+$/u', $value)) {
            $this->errors[$field] = "Le champ \"$label\" ne doit contenir que des lettres.";
        }
        return $this;
    }

    public function date(string $field, string $value, string $label): self {
        if ($value === '') {
            return $this;
        }

        $parts = explode('-', $value);
        if (count($parts) !== 3) {
            $this->errors[$field] = "Le champ \"$label\" doit etre une date valide.";
            return $this;
        }

        [$year, $month, $day] = array_map('intval', $parts);
        if (!checkdate($month, $day, $year)) {
            $this->errors[$field] = "Le champ \"$label\" doit etre une date valide.";
        }

        return $this;
    }

    public function isValid(): bool {
        return empty($this->errors);
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getError(string $field): string {
        return $this->errors[$field] ?? '';
    }
}
