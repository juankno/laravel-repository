<?php

namespace Juankno\Repository\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:repository {name} {model?}';
    protected $description = 'Crea un repositorio con su contrato e implementación';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $model = Str::studly($this->argument('model') ?? $name);

        if (!Str::endsWith($name, 'Repository')) {
            $name .= 'Repository';
        }

        if ($this->argument('model')) {
            $fullModelClass = "App\\Models\\{$model}";

            if (!class_exists($fullModelClass)) {
                $this->error("El modelo {$model} no existe.");
                return;
            }

            if (!is_subclass_of($fullModelClass, Model::class)) {
                $this->error("{$model} no es un modelo válido de Eloquent.");
                return;
            }
        }

        $paths = [
            app_path('Repositories'),
            app_path('Repositories/Contracts'),
        ];

        foreach ($paths as $path) {
            if (!File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }

        $repositoryPath = app_path("Repositories/{$name}.php");
        $interfacePath = app_path("Repositories/Contracts/{$name}Interface.php");

        if (File::exists($interfacePath)) {
            $this->warn("La interfaz {$name}Interface ya existe.");
        } else {
            File::put($interfacePath, $this->getInterfaceContent($name));
            $this->info("Interfaz {$name}Interface creada correctamente.");
        }

        if (File::exists($repositoryPath)) {
            $this->warn("El repositorio {$name} ya existe.");
        } else {
            File::put($repositoryPath, $this->getRepositoryContent($name, $model));
            $this->info("Repositorio {$name} creado correctamente.");
        }
    }

    protected function getInterfaceContent($name)
    {
        return <<<PHP
        <?php

        namespace App\Repositories\Contracts;

        interface {$name}Interface
        {
            public function all();
            public function find(\$id);
            public function create(array \$data);
            public function update(\$id, array \$data);
            public function delete(\$id);
        }
        PHP;
    }

    protected function getRepositoryContent($name, $model)
    {
        $modelImport = $this->argument('model') ? "use App\Models\\{$model};" : '';
        $modelVariable = Str::camel($model);
        $modelProperty = $this->argument('model') ? "protected \${$modelVariable};" : '';
        $modelConstructor = $this->argument('model')
            ? "public function __construct({$model} \${$modelVariable}) {\n        \$this->{$modelVariable} = \${$modelVariable};\n    }"
            : '';

        return <<<PHP
        <?php

        namespace App\Repositories;

        use App\Repositories\Contracts\\{$name}Interface;
        {$modelImport}

        class {$name} implements {$name}Interface
        {
            {$modelProperty}

            {$modelConstructor}

            public function all()
            {
                return \$this->{$modelVariable}->all();
            }

            public function find(\$id)
            {
                return \$this->{$modelVariable}->find(\$id);
            }

            public function create(array \$data)
            {
                return \$this->{$modelVariable}->create(\$data);
            }

            public function update(\$id, array \$data)
            {
                \$model = \$this->{$modelVariable}->find(\$id);
                return \$model ? \$model->update(\$data) : false;
            }

            public function delete(\$id)
            {
                \$model = \$this->{$modelVariable}->find(\$id);
                return \$model ? \$model->delete() : false;
            }
        }
        PHP;
    }
}
