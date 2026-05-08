<?php

class LoginSecurity {
    public function getTemporaryLockDuration(int $failedAttempts): int {
        return match ($failedAttempts) {
            5 => 30,
            6 => 60,
            7 => 300,
            default => 0,
        };
    }

    public function isPermanentLock(int $failedAttempts): bool {
        return $failedAttempts > 7;
    }

    public function formatSeconds(int $seconds): string {
        if ($seconds < 60) {
            return $seconds . ' secondes';
        }

        $minutes = (int) ceil($seconds / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    }
}
