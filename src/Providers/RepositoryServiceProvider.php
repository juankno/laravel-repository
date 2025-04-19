<?php

namespace Juankno\Repository\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register the command as a singleton to facilitate dependency injection
        $this->app->singleton(
            'repository.generator',
            function () {
                return new \Juankno\Repository\Commands\MakeRepositoryCommand();
            }
        );

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/repository.php', 'repository'
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

            // Publish configuration
            $this->publishes([
                __DIR__.'/../../config/repository.php' => config_path('repository.php'),
            ], 'repository-config');
        }

        // Allow users to publish the provider
        $this->publishes([
            __DIR__ . '/../../stubs/RepositoryServiceProvider.stub' => app_path('Providers/RepositoryServiceProvider.php'),
        ], 'repository-provider');
    }

    /**
     * Automatically registers repositories and their interfaces
     * 
     * @return void
     */
    protected function registerRepositories()
    {
        // Check if cache should be used
        $useCache = config('repository.cache.enabled', false);
        $cacheKey = config('repository.cache.key', 'laravel_repository_bindings');
        $cacheTTL = config('repository.cache.ttl', 60);
        
        // Get from cache if enabled and not in debug mode
        if ($useCache && !config('app.debug') && Cache::has($cacheKey)) {
            $bindings = Cache::get($cacheKey);
            
            foreach ($bindings as $interface => $implementation) {
                $this->app->bind($interface, $implementation);
            }
            
            return;
        }

        $bindings = [];
        
        // Register user custom bindings first
        $userBindings = $this->registerCustomBindings();
        $bindings = array_merge($bindings, $userBindings);
        
        // Register default bindings from configuration
        $configBindings = $this->registerConfigBindings();
        $bindings = array_merge($bindings, $configBindings);
        
        // Process repository directories
        $basePath = app_path('Repositories');
        
        if (!File::exists($basePath)) {
            // Create directory if it doesn't exist to avoid errors
            try {
                File::makeDirectory($basePath, 0755, true);
            } catch (\Exception $e) {
                // Do nothing if it can't be created - this is just preventive
            }
        } else {
            // Process main directory
            $discoveredBindings = $this->processDirectory($basePath);
            $bindings = array_merge($bindings, $discoveredBindings);
            
            // Process additional directories if configured
            $additionalDirectories = config('repository.directories', []);
            foreach ($additionalDirectories as $directory) {
                if (File::exists($directory)) {
                    $additionalBindings = $this->processDirectory($directory);
                    $bindings = array_merge($bindings, $additionalBindings);
                }
            }
        }
        
        // Save to cache if enabled and not in debug mode
        if ($useCache && !config('app.debug')) {
            $expiresAt = Carbon::now()->addMinutes($cacheTTL);
            Cache::put($cacheKey, $bindings, $expiresAt);
        }
    }

    /**
     * Register custom bindings from the user's RepositoryServiceProvider
     * 
     * @return array Custom bindings registered
     */
    protected function registerCustomBindings()
    {
        $bindings = [];
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');
        
        if (File::exists($providerPath)) {
            // Provider has been published, do nothing additional
            // as that provider will handle custom bindings
        }
        
        return $bindings;
    }

    /**
     * Register bindings from configuration
     * 
     * @return array Bindings from configuration
     */
    protected function registerConfigBindings()
    {
        $bindings = [];
        $configBindings = config('repository.bindings', []);
        
        foreach ($configBindings as $bindingConfig) {
            if (isset($bindingConfig['interface']) && isset($bindingConfig['implementation'])) {
                $interface = $bindingConfig['interface'];
                $implementation = $bindingConfig['implementation'];
                
                if (interface_exists($interface) && class_exists($implementation)) {
                    $this->app->bind($interface, $implementation);
                    $bindings[$interface] = $implementation;
                }
            }
        }
        
        return $bindings;
    }

    /**
     * Process a directory recursively looking for repositories
     * 
     * @param string $directory Directory to process
     * @return array Bindings found in the format [interface => implementation]
     */
    protected function processDirectory($directory)
    {
        $bindings = [];
        
        // If the directory doesn't exist, exit
        if (!File::exists($directory)) {
            return $bindings;
        }

        try {
            foreach (File::allFiles($directory) as $file) {
                // Skip contract directories
                if (Str::contains($file->getRelativePath(), 'Contracts')) {
                    continue;
                }

                // Process only PHP files
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                // Get the FQN of the class from the file path
                $class = $this->getClassFromPath($file->getPathname());

                // Skip if the class doesn't exist or doesn't end with Repository
                // or if it's BaseRepository or an abstract class
                if (!class_exists($class) || 
                    !Str::endsWith($class, 'Repository') || 
                    $class === 'App\\Repositories\\BaseRepository') {
                    continue;
                }

                // Check if the class is abstract
                try {
                    $reflectionClass = new \ReflectionClass($class);
                    if ($reflectionClass->isAbstract()) {
                        continue;
                    }
                } catch (\ReflectionException $e) {
                    continue;
                }

                // Build the interface name
                $interface = $this->buildInterfaceName($class);

                // Bind if the interface exists
                if (interface_exists($interface)) {
                    if (!$this->app->bound($interface)) {
                        $this->app->bind($interface, $class);
                        $bindings[$interface] = $class;
                    }
                }
            }
        } catch (\Exception $e) {
            if (config('app.debug')) {
                // Only log errors in debug mode to avoid filling logs in production
                Log::warning("Error processing repository directories: " . $e->getMessage());
            }
        }
        
        return $bindings;
    }

    /**
     * Get the fully qualified class name from the file path
     * 
     * @param string $path File path
     * @return string Fully qualified class name
     */
    protected function getClassFromPath($path)
    {
        $appPath = app_path();
        $relativePath = Str::after($path, $appPath . DIRECTORY_SEPARATOR);
        $relativePath = str_replace('.php', '', $relativePath);
        $namespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        return 'App\\' . $namespace;
    }

    /**
     * Build the interface name based on repository class name
     * 
     * @param string $class Fully qualified class name
     * @return string Fully qualified interface name
     */
    protected function buildInterfaceName($class)
    {
        $baseName = class_basename($class);
        $namespace = Str::beforeLast($class, '\\' . $baseName);
        
        // Build interface name by replacing 'Repository' with 'RepositoryInterface'
        // or adding 'Interface' at the end if it doesn't end with 'Repository'
        $interfaceName = Str::endsWith($baseName, 'Repository') 
            ? Str::replaceLast('Repository', 'RepositoryInterface', $baseName)
            : $baseName . 'Interface';

        // Replace 'Repositories' with 'Repositories\Contracts' in the namespace
        return str_replace('Repositories\\', 'Repositories\\Contracts\\', $namespace) . '\\' . $interfaceName;
    }
}
