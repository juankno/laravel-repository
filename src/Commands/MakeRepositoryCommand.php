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
        $modelProperty = $this->argument('model') ? "protected \${$model};" : '';
        $modelConstructor = $this->argument('model') ? "public function __construct({$model} \$model)\n            {\n                \$this->{$model} = \$model;\n            }" : '';

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
                return \$this->{$model}->all();
            }

            public function find(\$id)
            {
                return \$this->{$model}->find(\$id);
            }

            public function create(array \$data)
            {
                return \$this->{$model}->create(\$data);
            }

            public function update(\$id, array \$data)
            {
                \$model = \$this->{$model}->find(\$id);
                return \$model ? \$model->update(\$data) : false;
            }

            public function delete(\$id)
            {
                return \$this->{$model}->destroy(\$id);
            }
        }
        PHP;
    }
}
