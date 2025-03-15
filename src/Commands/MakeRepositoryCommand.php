<?php

namespace Juankno\Repository\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:repository {name} {model?}';
    protected $description = 'Crea un repositorio con su contrato e implementación';

    public function handle()
    {
        $name = $this->argument('name');
        $model = $this->argument('model') ?? $name;

        if (substr($name, -10) !== 'Repository') {
            $name .= 'Repository';
        }

        if ($this->argument('model') && !class_exists("App\\Models\\{$model}")) {
            $this->error("El modelo {$model} no existe.");
            return;
        }

        $repositoryPath = app_path("Repositories/{$name}.php");
        $interfacePath = app_path("Repositories/Contracts/{$name}Interface.php");

        if (!File::isDirectory(app_path('Repositories/Contracts'))) {
            File::makeDirectory(app_path('Repositories/Contracts'), 0755, true);
        }

        if (!File::exists($interfacePath)) {
            File::put($interfacePath, $this->getInterfaceContent($name));
        }

        if (!File::exists($repositoryPath)) {
            File::put($repositoryPath, $this->getRepositoryContent($name, $model));
        }

        $this->info("Repositorio {$name} creado correctamente.");
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
        $modelVariable = lcfirst($model);
        $modelProperty = $this->argument('model') ? "protected \${$modelVariable};" : '';
        $modelConstructor = $this->argument('model') ? "public function __construct({$model} \${$modelVariable})\n    {\n        \$this->{$modelVariable} = \${$modelVariable};\n    }" : '';

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
                return \$this->{$modelVariable}->destroy(\$id);
            }
        }
        PHP;
    }
}
