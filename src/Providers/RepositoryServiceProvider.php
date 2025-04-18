<?php

namespace Juankno\Repository\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
            return;
        }

        // Procesar directorios de repositorios
        $this->processDirectory($basePath);
    }

    /**
     * Procesa recursivamente un directorio buscando repositorios
     * 
     * @param string $directory Directorio a procesar
     * @return void
     */
    protected function processDirectory($directory)
    {
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
                $this->app->bind($interface, $class);
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
