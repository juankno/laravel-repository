<?php

namespace Juankno\Repository\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Registrar el comando como un singleton para facilitar la inyección de dependencias
        $this->app->singleton(
            'repository.generator',
            function () {
                return new \Juankno\Repository\Commands\MakeRepositoryCommand();
            }
        );

        // Registrar repositorios después de que todos los proveedores estén registrados
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

    /**
     * Registra automáticamente los repositorios y sus interfaces
     * 
     * @return void
     */
    protected function registerRepositories()
    {
        $basePath = app_path('Repositories');

        if (!File::exists($basePath)) {
            // Crear el directorio si no existe para evitar errores
            try {
                File::makeDirectory($basePath, 0755, true);
            } catch (\Exception $e) {
                // No hacer nada si no se puede crear - esto es solo preventivo
            }
            return;
        }

        // Registrar primero cualquier repositorio personalizado del usuario
        $this->registerCustomBindings();

        // Procesar directorios de repositorios
        $this->processDirectory($basePath);

        // Registrar manualmente algunos bindings comunes si existen pero no se detectaron
        $this->registerCommonBindings();
    }

    /**
     * Registra bindings personalizados desde el archivo RepositoryServiceProvider del usuario
     */
    protected function registerCustomBindings()
    {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');
        
        if (File::exists($providerPath)) {
            // El provider ya ha sido publicado, no hacemos nada adicional
            // ya que ese provider se encargará de los bindings personalizados
            return;
        }
    }

    /**
     * Registra algunos bindings comunes que podrían no ser detectados automáticamente
     */
    protected function registerCommonBindings()
    {
        // Lista de bindings comunes para verificar
        $commonBindings = [
            'User' => [
                'interface' => 'App\\Repositories\\Contracts\\UserRepositoryInterface',
                'implementation' => 'App\\Repositories\\UserRepository',
            ],
            'Auth' => [
                'interface' => 'App\\Repositories\\Contracts\\AuthRepositoryInterface',
                'implementation' => 'App\\Repositories\\AuthRepository',
            ],
            'Post' => [
                'interface' => 'App\\Repositories\\Contracts\\PostRepositoryInterface',
                'implementation' => 'App\\Repositories\\PostRepository',
            ],
        ];

        foreach ($commonBindings as $binding) {
            if (interface_exists($binding['interface']) && class_exists($binding['implementation'])) {
                if (!$this->app->bound($binding['interface'])) {
                    $this->app->bind($binding['interface'], $binding['implementation']);
                }
            }
        }
    }

    /**
     * Procesa recursivamente un directorio buscando repositorios
     * 
     * @param string $directory Directorio a procesar
     * @return void
     */
    protected function processDirectory($directory)
    {
        // Si el directorio no existe, salir
        if (!File::exists($directory)) {
            return;
        }

        try {
            foreach (File::allFiles($directory) as $file) {
                // Omitir directorios de contratos
                if (Str::contains($file->getRelativePath(), 'Contracts')) {
                    continue;
                }

                // Procesar solo archivos PHP
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                // Obtener el FQN de la clase desde la ruta del archivo
                $class = $this->getClassFromPath($file->getPathname());

                // Omitir si la clase no existe o no termina con Repository
                // o si es BaseRepository o una clase abstracta
                if (!class_exists($class) || 
                    !Str::endsWith($class, 'Repository') || 
                    $class === 'App\\Repositories\\BaseRepository') {
                    continue;
                }

                // Verificar si la clase es abstracta
                try {
                    $reflectionClass = new \ReflectionClass($class);
                    if ($reflectionClass->isAbstract()) {
                        continue;
                    }
                } catch (\ReflectionException $e) {
                    continue;
                }

                // Construir el nombre de la interfaz
                $interface = $this->buildInterfaceName($class);

                // Enlazar si la interfaz existe
                if (interface_exists($interface)) {
                    if (!$this->app->bound($interface)) {
                        $this->app->bind($interface, $class);
                    }
                }
            }
        } catch (\Exception $e) {
            if (config('app.debug')) {
                // Solo registrar errores en modo debug para evitar llenar logs en producción
                Log::warning("Error al procesar directorios de repositorios: " . $e->getMessage());
            }
        }
    }

    /**
     * Obtiene el nombre completamente cualificado de la clase desde la ruta del archivo
     * 
     * @param string $path Ruta del archivo
     * @return string Nombre completamente cualificado de la clase
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
     * Construye el nombre de la interfaz basado en el nombre de la clase del repositorio
     * 
     * @param string $class Nombre completamente cualificado de la clase
     * @return string Nombre completamente cualificado de la interfaz
     */
    protected function buildInterfaceName($class)
    {
        $baseName = class_basename($class);
        $namespace = Str::beforeLast($class, '\\' . $baseName);
        
        // Construir el nombre de la interfaz reemplazando 'Repository' por 'RepositoryInterface'
        // o agregando 'Interface' al final si no termina en 'Repository'
        $interfaceName = Str::endsWith($baseName, 'Repository') 
            ? Str::replaceLast('Repository', 'RepositoryInterface', $baseName)
            : $baseName . 'Interface';

        // Reemplazar 'Repositories' por 'Repositories\Contracts' en el namespace
        return str_replace('Repositories\\', 'Repositories\\Contracts\\', $namespace) . '\\' . $interfaceName;
    }
}
