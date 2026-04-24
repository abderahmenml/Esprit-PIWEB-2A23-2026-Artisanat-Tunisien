<?php
/**
 * Classe Validator — Validation côté serveur (sans HTML5)
 * Toute validation est effectuée ici en PHP pur
 */
class Validator {
    private array $errors = [];

    /**
     * Valide qu'un champ n'est pas vide
     */
    public function required(string $field, mixed $value, string $label): self {
        if ($value === null || trim((string)$value) === '') {
            $this->errors[$field] = "Le champ « $label » est obligatoire.";
        }
        return $this;
    }

    /**
     * Valide le format d'un email
     */
    public function email(string $field, string $value, string $label = 'Email'): self {
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "Le champ « $label » doit être une adresse e-mail valide.";
        }
        return $this;
    }

    /**
     * Valide la longueur minimale
     */
    public function minLength(string $field, string $value, int $min, string $label): self {
        if ($value !== '' && mb_strlen($value) < $min) {
            $this->errors[$field] = "Le champ « $label » doit contenir au moins $min caractères.";
        }
        return $this;
    }

    /**
     * Valide la longueur maximale
     */
    public function maxLength(string $field, string $value, int $max, string $label): self {
        if ($value !== '' && mb_strlen($value) > $max) {
            $this->errors[$field] = "Le champ « $label » ne doit pas dépasser $max caractères.";
        }
        return $this;
    }

    /**
     * Valide que deux valeurs sont identiques
     */
    public function matches(string $field, string $value1, string $value2, string $label): self {
        if ($value1 !== $value2) {
            $this->errors[$field] = "Le champ « $label » ne correspond pas.";
        }
        return $this;
    }

    /**
     * Valide qu'une valeur fait partie d'une liste autorisée
     */
    public function inList(string $field, string $value, array $list, string $label): self {
        if (!in_array($value, $list, true)) {
            $this->errors[$field] = "Le champ « $label » contient une valeur non autorisée.";
        }
        return $this;
    }

    /**
     * Valide uniquement les lettres et espaces (pour noms)
     */
    public function alpha(string $field, string $value, string $label): self {
        if ($value !== '' && !preg_match('/^[\p{L}\s\-]+$/u', $value)) {
            $this->errors[$field] = "Le champ « $label » ne doit contenir que des lettres.";
        }
        return $this;
    }

    /**
     * Retourne vrai si aucune erreur
     */
    public function isValid(): bool {
        return empty($this->errors);
    }

    /**
     * Retourne toutes les erreurs
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Retourne la première erreur d'un champ
     */
    public function getError(string $field): string {
        return $this->errors[$field] ?? '';
    }
}
