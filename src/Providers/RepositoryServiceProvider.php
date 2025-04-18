<?php

namespace Juankno\Repository\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            'repository.generator',
            function () {
                return new \Juankno\Repository\Commands\MakeRepositoryCommand();
            }
        );

        // Register repositories after all providers are registered
        $this->app->booted(function () {
            $this->registerRepositories();
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Juankno\Repository\Commands\MakeRepositoryCommand::class,
            ]);
        }

        // Permitir que los usuarios publiquen el provider
        $this->publishes([
            __DIR__ . '/../../stubs/RepositoryServiceProvider.stub' => app_path('Providers/RepositoryServiceProvider.php'),
        ], 'repository-provider');
    }

    protected function registerRepositories()
    {
        $basePath = app_path('Repositories');

        if (!File::exists($basePath)) {
            return;
        }

        // Process repository files
        $this->processDirectory($basePath);
    }

    protected function processDirectory($directory)
    {
        foreach (File::allFiles($directory) as $file) {
            // Skip contracts directory
            if (Str::contains($file->getRelativePath(), 'Contracts')) {
                continue;
            }

            // Only process PHP files
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // Get class FQN from file path
            $class = $this->getClassFromPath($file->getPathname());

            // Skip if class doesn't exist or doesn't end with Repository
            if (!class_exists($class) || !Str::endsWith($class, 'Repository')) {
                continue;
            }

            // Build interface name
            $interface = $this->buildInterfaceName($class);

            // Bind if interface exists
            if (interface_exists($interface)) {
                $this->app->bind($interface, $class);
            }
        }
    }

    protected function getClassFromPath($path)
    {
        $appPath = app_path();
        $relativePath = Str::after($path, $appPath . DIRECTORY_SEPARATOR);
        $relativePath = str_replace('.php', '', $relativePath);
        $namespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        return 'App\\' . $namespace;
    }

    protected function buildInterfaceName($class)
    {
        $baseName = class_basename($class);
        $namespace = Str::beforeLast($class, '\\' . $baseName);
        $interfaceName = Str::replaceLast('Repository', 'RepositoryInterface', $baseName);

        return str_replace('Repositories\\', 'Repositories\\Contracts\\', $namespace) . '\\' . $interfaceName;
    }
}
