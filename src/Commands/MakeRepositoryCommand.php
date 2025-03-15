<?php

namespace Juankno\Repository\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:repository {name}';
    protected $description = 'Crea un repositorio con su contrato e implementación';

    public function handle()
    {
        $name = $this->argument('name');
        
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
            File::put($repositoryPath, $this->getRepositoryContent($name));
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

    protected function getRepositoryContent($name)
    {
        return <<<PHP
        <?php

        namespace App\Repositories;

        use App\Repositories\Contracts\\{$name}Interface;
        use App\Models\\{$name};

        class {$name}Repository implements {$name}Interface
        {
            protected \${$name};

            public function __construct({$name} \$model)
            {
                \$this->{$name} = \$model;
            }

            public function all()
            {
                return \$this->{$name}->all();
            }

            public function find(\$id)
            {
                return \$this->{$name}->find(\$id);
            }

            public function create(array \$data)
            {
                return \$this->{$name}->create(\$data);
            }

            public function update(\$id, array \$data)
            {
                \$model = \$this->{$name}->find(\$id);
                return \$model ? \$model->update(\$data) : false;
            }

            public function delete(\$id)
            {
                return \$this->{$name}->destroy(\$id);
            }
        }
        PHP;
    }
}
