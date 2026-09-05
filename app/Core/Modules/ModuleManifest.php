<?php

declare(strict_types=1);

namespace NovaNuke\Core\Modules;

use InvalidArgumentException;

final readonly class ModuleManifest
{
    /** @param array<string, string> $dependencies
     *  @param list<string> $permissions
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
        if (! preg_match('/^[a-z][a-z0-9-]{0,99}$/', $data['slug'])) {
            throw new InvalidArgumentException('Module slug must use lowercase letters, numbers and hyphens.');
        }
        if (! preg_match('/^\d+\.\d+\.\d+(?:-[a-zA-Z0-9.-]+)?$/', $data['version'])) {
            throw new InvalidArgumentException('Module version must use semantic versioning.');
        }
        $dependencies = $data['dependencies'] ?? [];
        $permissions = $data['permissions'] ?? [];
        if (! is_array($dependencies) || ! is_array($permissions)) {
            throw new InvalidArgumentException('Module dependencies and permissions must be arrays.');
        }
        if (! preg_match('/^Modules\\\\[A-Za-z][A-Za-z0-9_\\\\]+$/', $data['provider'])) {
            throw new InvalidArgumentException('Module provider must use the Modules namespace.');
        }
        foreach ($dependencies as $dependency => $minimumVersion) {
            if (! is_string($dependency) || ! preg_match('/^[a-z][a-z0-9-]{0,99}$/', $dependency)) {
                throw new InvalidArgumentException('Invalid module dependency slug.');
            }
            if ($dependency === $data['slug']) {
                throw new InvalidArgumentException('A module cannot depend on itself.');
            }
            if (! is_string($minimumVersion) || ! preg_match('/^\d+\.\d+\.\d+/', $minimumVersion)) {
                throw new InvalidArgumentException("Invalid dependency version for {$dependency}.");
            }
        }
        foreach ($permissions as $permission) {
            if (! is_string($permission)) {
                throw new InvalidArgumentException('Module permissions must be strings.');
            }
        }

        return new self(
            trim($data['name']),
            $data['slug'],
            $data['version'],
            (string) ($data['description'] ?? ''),
            (string) ($data['author'] ?? ''),
            $data['provider'],
            $data['cms_min_version'],
            $data['php_min_version'],
            array_map('strval', $dependencies),
            array_values(array_map('strval', $permissions)),
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
        ];
    }
}
