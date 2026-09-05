<?php

declare(strict_types=1);

namespace NovaNuke\Core\Modules;

use NovaNuke\Core\Container\Container;
use NovaNuke\Core\Events\EventDispatcher;
use NovaNuke\Core\Http\Routing\Router;
use PDO;
use RuntimeException;
use Throwable;
use NovaNuke\Core\I18n\Translator;

final class ModuleManager
{
    public function __construct(
        private readonly PDO $database,
        private readonly ModuleDetector $detector,
        private readonly ModuleRepository $repository,
        private readonly ModuleMigrator $migrator,
        private readonly ModuleCompatibilityChecker $compatibility,
        private readonly Container $container,
        private readonly Router $router,
        private readonly EventDispatcher $events,
        private readonly Translator $translator,
    ) {
    }

    /** @return array<string, array<string, mixed>> */
    public function inventory(): array
    {
        $detected = $this->detector->detect();
        $installed = $this->repository->all();
        $inventory = [];

        foreach ($detected as $slug => $manifest) {
            $compatibility = $this->compatibility->check($manifest, $installed);
            $record = $installed[$slug] ?? null;
            $inventory[$slug] = [
                'manifest' => $manifest,
                'installed' => $record !== null,
                'enabled' => (bool) ($record['enabled'] ?? false),
                'installed_version' => $record['installed_version'] ?? null,
                'update_available' => $record !== null
                    && version_compare($manifest->version, (string) $record['installed_version'], '>'),
                'compatible' => $compatibility->compatible,
                'compatibility_reason' => $compatibility->reason,
                'last_error' => $record['last_error'] ?? null,
                'missing_files' => false,
            ];
        }

        foreach ($installed as $slug => $record) {
            if (isset($inventory[$slug])) {
                continue;
            }
            $inventory[$slug] = [
                'manifest' => null,
                'installed' => true,
                'enabled' => (bool) $record['enabled'],
                'installed_version' => $record['installed_version'],
                'update_available' => false,
                'compatible' => false,
                'compatibility_reason' => 'Module files are missing from disk.',
                'last_error' => $record['last_error'],
                'missing_files' => true,
                'name' => $record['name'],
            ];
        }

        ksort($inventory);
        return $inventory;
    }

    public function install(string $slug): void
    {
        $manifest = $this->manifest($slug);
        $installed = $this->repository->all();
        if (isset($installed[$slug])) {
            throw new RuntimeException('The module is already installed.');
        }
        $check = $this->compatibility->check($manifest, $installed);
        if (! $check->compatible) {
            throw new RuntimeException((string) $check->reason);
        }

        $this->migrator->run($manifest);
        $this->registerPermissions($manifest);
        $this->repository->install($manifest);
    }

    public function update(string $slug): void
    {
        $manifest = $this->manifest($slug);
        $installed = $this->repository->all();
        $record = $installed[$slug] ?? null;
        if ($record === null) {
            throw new RuntimeException('The module is not installed.');
        }
        if (version_compare($manifest->version, (string) $record['installed_version'], '<=')) {
            throw new RuntimeException('No newer module version is available.');
        }
        $check = $this->compatibility->check($manifest, $installed);
        if (! $check->compatible) {
            throw new RuntimeException((string) $check->reason);
        }

        $this->migrator->run($manifest);
        $this->registerPermissions($manifest);
        $this->repository->install($manifest);
    }

    public function enable(string $slug): void
    {
        $manifest = $this->manifest($slug);
        $installed = $this->repository->all();
        if (! isset($installed[$slug])) {
            throw new RuntimeException('Install the module before enabling it.');
        }
        $check = $this->compatibility->check($manifest, $installed);
        if (! $check->compatible) {
            throw new RuntimeException((string) $check->reason);
        }
        foreach (array_keys($manifest->dependencies) as $dependency) {
            if (! ($installed[$dependency]['enabled'] ?? false)) {
                throw new RuntimeException("Enable dependency first: {$dependency}");
            }
        }
        $this->provider($manifest);
        $this->repository->setEnabled($slug, true);
    }

    public function disable(string $slug): void
    {
        $installed = $this->repository->all();
        if (! isset($installed[$slug])) {
            throw new RuntimeException('The module is not installed.');
        }
        foreach ($this->detector->detect() as $other) {
            if (($installed[$other->slug]['enabled'] ?? false) && isset($other->dependencies[$slug])) {
                throw new RuntimeException("Disable dependent module first: {$other->slug}");
            }
        }
        $this->repository->setEnabled($slug, false);
    }

    public function uninstall(string $slug, bool $deleteData): void
    {
        $manifest = $this->manifest($slug);
        $this->disable($slug);
        if ($deleteData) {
            $this->migrator->rollbackAll($manifest);
        }
        $permissions = $this->database->prepare('DELETE FROM permissions WHERE module_slug = :module_slug');
        $permissions->execute(['module_slug' => $slug]);
        $this->repository->remove($slug);
    }

    public function bootEnabled(): void
    {
        if (! $this->repository->available()) {
            return;
        }
        $detected = $this->detector->detect();
        $installed = $this->repository->all();
        $enabled = array_filter($installed, static fn (array $module): bool => $module['enabled']);
        $pending = $enabled;
        $registered = [];
        $lifecycles = [];

        while ($pending !== []) {
            $progress = false;
            foreach ($pending as $slug => $record) {
                $manifest = $detected[$slug] ?? null;
                if ($manifest !== null) {
                    $compatibility=$this->compatibility->check($manifest,$installed);
                    if(!$compatibility->compatible){$this->repository->setError($slug,(string)$compatibility->reason);unset($pending[$slug]);$progress=true;continue;}
                    $unresolved = array_diff(array_keys($manifest->dependencies), array_keys($registered));
                    if ($unresolved !== []) {
                        continue;
                    }
                }
                $lifecycle=$this->registerOne($slug,$manifest);
                if($lifecycle!==null){$registered[$slug]=true;$lifecycles[$slug]=$lifecycle;}
                unset($pending[$slug]);
                $progress = true;
            }
            if (! $progress) {
                foreach (array_keys($pending) as $slug) {
                    $this->repository->setError($slug, 'Module dependency cycle or inactive dependency detected.');
                }
                break;
            }
        }
        foreach($lifecycles as$slug=>$lifecycle){try{$lifecycle['provider']->boot($lifecycle['context']);}catch(Throwable$error){$this->repository->setError($slug,$error->getMessage());error_log("Module {$slug} failed to boot: {$error->getMessage()}");}}
    }

    /** @return array{provider:ModuleInterface,context:ModuleContext}|null */
    private function registerOne(string $slug, ?ModuleManifest $manifest): ?array
    {
        if ($manifest === null) {
            $this->repository->setError($slug, 'Module files are missing from disk.');
            return null;
        }
        try {
            $this->translator->addNamespace($slug, $manifest->path . '/language');
            $provider = $this->provider($manifest);
            $context = new ModuleContext(
                $manifest,
                $this->container,
                $this->router,
                $this->events,
                $manifest->path,
            );
            $provider->register($context);
            return ['provider'=>$provider,'context'=>$context];
        } catch (Throwable $error) {
            $this->repository->setError($slug, $error->getMessage());
            error_log("Module {$slug} failed to register: {$error->getMessage()}");
            return null;
        }
    }

    private function manifest(string $slug): ModuleManifest
    {
        if (! preg_match('/^[a-z][a-z0-9-]{0,99}$/', $slug)) {
            throw new RuntimeException('Invalid module slug.');
        }
        $manifest = $this->detector->detect()[$slug] ?? null;
        if ($manifest === null) {
            throw new RuntimeException('Module files were not found.');
        }

        return $manifest;
    }

    private function provider(ModuleManifest $manifest): ModuleInterface
    {
        if (! class_exists($manifest->provider)) {
            throw new RuntimeException("Module provider class was not found: {$manifest->provider}");
        }
        $provider = new ($manifest->provider)();
        if (! $provider instanceof ModuleInterface) {
            throw new RuntimeException('Module provider must implement ModuleInterface.');
        }

        return $provider;
    }

    private function registerPermissions(ModuleManifest $manifest): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO permissions (name, slug, description, module_slug, created_at, updated_at) '
            . 'VALUES (:name, :slug, :description, :module_slug, UTC_TIMESTAMP(), UTC_TIMESTAMP()) '
            . 'ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), '
            . 'module_slug = VALUES(module_slug), updated_at = UTC_TIMESTAMP()'
        );
        foreach ($manifest->permissions as $permission) {
            if (! preg_match('/^' . preg_quote($manifest->slug, '/') . '\.[a-z][a-z0-9_.-]*$/', $permission)) {
                throw new RuntimeException("Invalid module permission slug: {$permission}");
            }
            $name = ucwords(str_replace(['.', '-', '_'], ' ', $permission));
            $statement->execute([
                'name' => $name,
                'slug' => $permission,
                'description' => "Permission provided by {$manifest->name}.",
                'module_slug' => $manifest->slug,
            ]);
        }
    }
}
