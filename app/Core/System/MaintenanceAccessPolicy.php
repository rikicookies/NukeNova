<?php

declare(strict_types=1);

namespace NovaNuke\Core\System;

final class MaintenanceAccessPolicy
{
    public function blocks(string $path, bool $installed, bool $enabled, bool $isSuperAdministrator): bool
    {
        if (! $installed || ! $enabled || $isSuperAdministrator) return false;

        foreach (['/login', '/logout', '/forgot-password', '/reset-password', '/admin', '/health'] as $allowed) {
            if ($path === $allowed || (in_array($allowed, ['/reset-password', '/admin'], true)
                && str_starts_with($path, $allowed . '/'))) {
                return false;
            }
        }
        return true;
    }
}
