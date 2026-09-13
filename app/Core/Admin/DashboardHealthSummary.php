<?php

declare(strict_types=1);

namespace NovaNuke\Core\Admin;

final class DashboardHealthSummary
{
    /** @param array<string,mixed> $system
     *  @return array{status:string,checks:list<array{label:string,value:string,status:string,url:string}>}
     */
    public function summarize(array $system): array
    {
        $migrations = is_array($system['migrations'] ?? null) ? $system['migrations'] : [];
        $pending = (int) ($migrations['pending_total'] ?? 0);
        $missing = (int) ($migrations['missing_total'] ?? 0);
        $recovery = (int) ($migrations['recovery_total'] ?? 0);
        $updates = (int) ($migrations['module_updates_total'] ?? 0);
        $unwritable = count(array_filter(
            is_array($system['writable'] ?? null) ? $system['writable'] : [],
            static fn (mixed $writable): bool => $writable !== true,
        ));
        $warnings = count(is_array($system['warnings'] ?? null) ? $system['warnings'] : []);

        $checks = [
            $this->check(
                'Maintenance mode',
                ($system['maintenance'] ?? false) ? 'Enabled' : 'Disabled',
                ($system['maintenance'] ?? false) ? 'warning' : 'ok',
                '/admin/settings',
            ),
            $this->check(
                'Database state',
                $recovery > 0 ? "{$recovery} migration recovery operation(s)" : ($missing > 0 ? "{$missing} missing migration file(s)" : ($pending + $updates > 0 ? ($pending + $updates) . ' pending change(s)' : 'Up to date')),
                $recovery + $missing > 0 ? 'error' : ($pending + $updates > 0 ? 'warning' : 'ok'),
                '/admin/system',
            ),
            $this->check(
                'Writable storage',
                $unwritable > 0 ? "{$unwritable} path(s) need attention" : 'Ready',
                $unwritable > 0 ? 'error' : 'ok',
                '/admin/system',
            ),
            $this->check(
                'Production configuration',
                $warnings > 0 ? "{$warnings} warning(s)" : 'Baseline checks passed',
                $warnings > 0 ? 'warning' : 'ok',
                '/admin/system',
            ),
        ];

        return [
            'status' => count(array_filter($checks, static fn (array $check): bool => $check['status'] !== 'ok')) > 0
                ? 'attention'
                : 'healthy',
            'checks' => $checks,
        ];
    }

    /** @return array{label:string,value:string,status:string,url:string} */
    private function check(string $label, string $value, string $status, string $url): array
    {
        return compact('label', 'value', 'status', 'url');
    }
}
