<?php
declare(strict_types=1);

function job_asset(string $path): string
{
    $base = function_exists('app_base_url') ? app_base_url() : '/';
    return $base . 'public/' . ltrim(str_replace('\\', '/', $path), '/');
}

function job_offer_status_badge_class(string $status): string
{
    return match ($status) {
        'published', 'open', 'active', 'actif' => 'bg-success',
        'paused' => 'bg-warning text-dark',
        'closed', 'completed' => 'bg-secondary',
        default => 'bg-info text-dark',
    };
}

function job_offer_status_label(string $status): string
{
    return match ($status) {
        'draft' => 'Brouillon',
        'published' => 'Publiée',
        'open', 'active', 'actif' => 'Ouverte',
        'paused' => 'En pause',
        'closed', 'completed' => 'Clôturée',
        default => ucfirst($status),
    };
}

function job_application_status_badge_class(string $status): string
{
    return match ($status) {
        'accepted' => 'bg-success',
        'rejected' => 'bg-danger',
        'reviewed', 'shortlisted', 'interview' => 'bg-info',
        'pending', 'submitted' => 'bg-secondary',
        default => 'bg-secondary',
    };
}

function job_escape(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
