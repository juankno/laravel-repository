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
        $path = app_path('Repositories');
        if (!File::exists($path)) {
            return;
        }

        foreach (File::allFiles($path) as $file) {
            $class = 'App\\Repositories\\' . $file->getFilenameWithoutExtension();
            $interface = str_replace('Repository', 'Interface', $class);

            if (interface_exists($interface)) {
                $this->app->bind($interface, $class);
            }
        }
    }
}
