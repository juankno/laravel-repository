<?php

namespace Juankno\Repository\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

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

        $this->registerRepositories();
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Juankno\Repository\Commands\MakeRepositoryCommand::class,
            ]);
        }
    }

    protected function registerRepositories()
    {
        $basePath = app_path('Repositories');

        if (!File::exists($basePath)) {
            return;
        }

        foreach (File::allFiles($basePath) as $file) {
            // Obtener la ruta relativa respecto a app_path('Repositories')
            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());

            // Convertir el path a namespace correcto
            $class = 'App\\Repositories\\' . str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativePath);

            // Construcción de la interfaz esperada en Contracts
            $interface = str_replace('Repositories\\', 'Repositories\\Contracts\\', $class);
            $interface = str_replace('Repository', 'Interface', $interface);

            if (interface_exists($interface)) {
                $this->app->bind($interface, $class);
            }
        }
    }
}
