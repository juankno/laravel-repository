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
        $model = Str::studly($this->argument('model') ?? class_basename($name));

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

        // Validación del modelo si se proporciona
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

        // Crear la interfaz si no existe
        if (File::exists($interfacePath)) {
            $this->warn("La interfaz {$repositoryName}Interface ya existe.");
        } else {
            File::put($interfacePath, $this->getInterfaceContent($repositoryName, implode('\\', $pathParts)));
            $this->info("Interfaz {$repositoryName}Interface creada correctamente.");
        }

        // Crear el repositorio si no existe
        if (File::exists($repositoryPath)) {
            $this->warn("El repositorio {$repositoryName} ya existe.");
        } else {
            File::put($repositoryPath, $this->getRepositoryContent($repositoryName, $model, implode('\\', $pathParts)));
            $this->info("Repositorio {$repositoryName} creado correctamente.");
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
