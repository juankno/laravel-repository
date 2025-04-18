<?php

namespace Juankno\Repository\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:repository {name} {model?} {--force}';
    protected $description = 'Create a repository with its contract and implementation';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $model = Str::studly($this->argument('model') ?? class_basename($name));

        if (preg_match('/[^A-Za-z0-9\/]/', $name)) {
            $this->error("The repository name contains invalid characters.");
            return;
        }

        // Extraer directorio y nombre del repositorio
        $pathParts = explode('/', $name);
        $repositoryName = Str::studly(array_pop($pathParts));

        // Asegurar que el nombre termine con "Repository"
        if (!Str::endsWith($repositoryName, 'Repository')) {
            $repositoryName .= 'Repository';
        }

        // Construir el directorio donde se guardará el repositorio
        $repositoryDirectory = app_path('Repositories/' . implode('/', $pathParts));
        $contractsDirectory = app_path('Repositories/Contracts/' . implode('/', $pathParts));

        // Asegurar que los directorios existen
        foreach ([$repositoryDirectory, $contractsDirectory] as $dir) {
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }

        // Construcción de rutas de archivos
        $repositoryPath = "{$repositoryDirectory}/{$repositoryName}.php";
        $interfacePath = "{$contractsDirectory}/{$repositoryName}Interface.php";

        // Validate the model if provided
        if ($this->argument('model')) {
            $fullModelClass = "App\\Models\\{$model}";

            if (!class_exists($fullModelClass)) {
                $this->error("The model {$model} does not exist.");
                return;
            }

            if (!is_subclass_of($fullModelClass, Model::class)) {
                $this->error("{$model} is not a valid Eloquent model.");
                return;
            }
        }

        // Create the interface if it does not exist
        if (File::exists($interfacePath) && !$this->option('force')) {
            $this->warn("The interface {$repositoryName}Interface already exists.");
        } else {
            File::put($interfacePath, $this->getInterfaceContent($repositoryName, implode('\\', $pathParts)));
            $this->info("Interface {$repositoryName}Interface created successfully.");
        }

        // Create the repository if it does not exist
        if (File::exists($repositoryPath)) {
            $this->warn("The repository {$repositoryName} already exists.");
        } else {
            File::put($repositoryPath, $this->getRepositoryContent($repositoryName, $model, implode('\\', $pathParts)));
            $this->info("Repository {$repositoryName} created successfully.");
        }
    }

    protected function getInterfaceContent($name, $namespace)
    {
        $namespace = $namespace ? "App\\Repositories\\Contracts\\{$namespace}" : "App\\Repositories\\Contracts";

        return <<<PHP
        <?php

        namespace {$namespace};

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

    protected function getRepositoryContent($name, $model, $namespace)
    {
        $namespace = $namespace ? "App\\Repositories\\{$namespace}" : "App\\Repositories";
        $contractNamespace = $namespace ? str_replace("App\\Repositories", "App\\Repositories\\Contracts", $namespace) : "App\\Repositories\\Contracts";
        $modelImport = $this->argument('model') ? "use App\\Models\\{$model};" : '';
        $modelVariable = Str::camel($model);
        $modelProperty = $this->argument('model') ? "protected \${$modelVariable};" : '';
        $modelConstructor = $this->argument('model')
            ? "public function __construct({$model} \${$modelVariable}) {\n        \$this->{$modelVariable} = \${$modelVariable};\n    }"
            : '';

        return <<<PHP
        <?php
    
        namespace {$namespace};
    
        use {$contractNamespace}\\{$name}Interface;
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
                return \$model ? tap(\$model)->update(\$data) : false;
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
