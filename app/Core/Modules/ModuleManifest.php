<?php

declare(strict_types=1);

namespace NovaNuke\Core\Modules;

use InvalidArgumentException;

final readonly class ModuleManifest
{
    private const SEMVER_PATTERN = '/^(0|[1-9]\\d*)\\.(0|[1-9]\\d*)\\.(0|[1-9]\\d*)(?:-[0-9A-Za-z-]+(?:\\.[0-9A-Za-z-]+)*)?(?:\\+[0-9A-Za-z-]+(?:\\.[0-9A-Za-z-]+)*)?$/';

    /** @param array<string, string> $dependencies
     *  @param list<string> $permissions
     *  @param list<string> $events
     */
    public function __construct(
        public string $name,
        public string $slug,
        public string $version,
        public string $description,
        public string $author,
        public string $provider,
        public string $cmsMinVersion,
        public string $phpMinVersion,
        public array $dependencies,
        public array $permissions,
        public array $events,
        public string $apiVersion,
        public string $path,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $path): self
    {
        foreach (['name', 'slug', 'version', 'provider', 'cms_min_version', 'php_min_version'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) === '') {
                throw new InvalidArgumentException("Module manifest field is required: {$field}");
            }
        }

        $slug = trim($data['slug']);
        $version = trim($data['version']);
        $provider = trim($data['provider']);
        $cmsMinVersion = trim($data['cms_min_version']);
        $phpMinVersion = trim($data['php_min_version']);

        if (! preg_match('/^[a-z][a-z0-9-]{0,99}$/', $slug)) {
            throw new InvalidArgumentException('Module slug must use lowercase letters, numbers and hyphens.');
        }
        self::assertSemanticVersion($version, 'Module version');
        self::assertSemanticVersion($cmsMinVersion, 'Module cms_min_version');
        self::assertSemanticVersion($phpMinVersion, 'Module php_min_version');

        if (! preg_match('/^Modules\\\\[A-Za-z][A-Za-z0-9_\\\\]+$/', $provider)) {
            throw new InvalidArgumentException('Module provider must use the Modules namespace.');
        }
        $directory = basename(rtrim(str_replace('\\', '/', $path), '/'));
        if ($directory === '' || ! str_starts_with($provider, 'Modules\\' . $directory . '\\')) {
            throw new InvalidArgumentException('Module provider must belong to its module namespace.');
        }

        $dependencies = $data['dependencies'] ?? [];
        $permissions = $data['permissions'] ?? [];
        $events = $data['events'] ?? [];
        if (! is_array($dependencies) || ! is_array($permissions) || ! is_array($events)) {
            throw new InvalidArgumentException('Module dependencies, permissions and events must be arrays.');
        }

        foreach ($dependencies as $dependency => $minimumVersion) {
            if (! is_string($dependency) || ! preg_match('/^[a-z][a-z0-9-]{0,99}$/', $dependency)) {
                throw new InvalidArgumentException('Invalid module dependency slug.');
            }
            if ($dependency === $slug) {
                throw new InvalidArgumentException('A module cannot depend on itself.');
            }
            if (! is_string($minimumVersion)) {
                throw new InvalidArgumentException("Invalid dependency version for {$dependency}.");
            }
            self::assertSemanticVersion(trim($minimumVersion), "Dependency version for {$dependency}");
        }

        $permissionPattern = '/^' . preg_quote($slug, '/') . '\\.[a-z][a-z0-9-]*(?:\\.[a-z][a-z0-9-]*)*$/';
        $seenPermissions = [];
        foreach ($permissions as $permission) {
            if (! is_string($permission) || ! preg_match($permissionPattern, $permission)) {
                throw new InvalidArgumentException("Module permission must begin with {$slug}. and use lowercase dot-separated identifiers.");
            }
            if (isset($seenPermissions[$permission])) {
                throw new InvalidArgumentException("Duplicate module permission: {$permission}");
            }
            $seenPermissions[$permission] = true;
        }

        $seenEvents = [];
        foreach ($events as $event) {
            if (! is_string($event) || ! preg_match('/^[a-z][a-z0-9-]*(?:\\.[a-z][a-z0-9-]*)*$/', $event) || strlen($event) > 120) {
                throw new InvalidArgumentException('Invalid module event name.');
            }
            if (isset($seenEvents[$event])) {
                throw new InvalidArgumentException("Duplicate module event: {$event}");
            }
            $seenEvents[$event] = true;
        }

        $apiVersion = trim((string) ($data['api_version'] ?? '1.0'));
        if (! preg_match('/^(0|[1-9]\\d*)\\.(0|[1-9]\\d*)$/', $apiVersion)) {
            throw new InvalidArgumentException('Module API version must use major.minor format.');
        }

        return new self(
            trim($data['name']),
            $slug,
            $version,
            (string) ($data['description'] ?? ''),
            (string) ($data['author'] ?? ''),
            $provider,
            $cmsMinVersion,
            $phpMinVersion,
            array_map(static fn (mixed $value): string => trim((string) $value), $dependencies),
            array_values(array_map('strval', $permissions)),
            array_values(array_map('strval', $events)),
            $apiVersion,
            $path,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'version' => $this->version,
            'description' => $this->description,
            'author' => $this->author,
            'provider' => $this->provider,
            'cms_min_version' => $this->cmsMinVersion,
            'php_min_version' => $this->phpMinVersion,
            'dependencies' => $this->dependencies,
            'permissions' => $this->permissions,
            'events' => $this->events,
            'api_version' => $this->apiVersion,
        ];
    }

    private static function assertSemanticVersion(string $version, string $label): void
    {
        if (! preg_match(self::SEMVER_PATTERN, $version)) {
            throw new InvalidArgumentException("{$label} must use semantic versioning.");
        }
    }
}
