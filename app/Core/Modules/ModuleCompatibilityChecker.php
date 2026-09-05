<?php

declare(strict_types=1);

namespace NovaNuke\Core\Modules;

final class ModuleCompatibilityChecker
{
    public function __construct(
        private readonly string $cmsVersion,
        private readonly string $phpVersion = PHP_VERSION,
    ) {
    }

    /** @param array<string, array<string, mixed>> $installed */
    public function check(ModuleManifest $manifest, array $installed): ModuleCompatibility
    {
        if (version_compare($this->phpVersion, $manifest->phpMinVersion, '<')) {
            return new ModuleCompatibility(false, "Requires PHP {$manifest->phpMinVersion} or newer.");
        }
        if (version_compare($this->cmsVersion, $manifest->cmsMinVersion, '<')) {
            return new ModuleCompatibility(false, "Requires NovaNuke {$manifest->cmsMinVersion} or newer.");
        }
        foreach ($manifest->dependencies as $slug => $minimumVersion) {
            if (! isset($installed[$slug])) {
                return new ModuleCompatibility(false, "Missing dependency: {$slug}");
            }
            if (version_compare((string) $installed[$slug]['installed_version'], $minimumVersion, '<')) {
                return new ModuleCompatibility(false, "Dependency {$slug} must be {$minimumVersion} or newer.");
            }
        }

        return new ModuleCompatibility(true);
    }
}
