<?php

namespace Juankno\Repository\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:repository {name} {model?} {--force} {--abstract}';
    protected $description = 'Create a repository with its contract and implementation';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $modelArg = $this->argument('model') ?? class_basename($name);
        
        if (Str::contains($modelArg, '\\')) {
            $model = $modelArg;
        } else {
            $model = Str::studly($modelArg);
        }

        if (preg_match('/[^A-Za-z0-9\/\\\\]/', $name)) {
            $this->error("The repository name contains invalid characters.");
            return;
        }

        $pathParts = explode('/', $name);
        $repositoryName = Str::studly(array_pop($pathParts));

        if (!Str::endsWith($repositoryName, 'Repository')) {
            $repositoryName .= 'Repository';
        }

        $repositoryDirectory = app_path('Repositories/' . implode('/', $pathParts));
        $contractsDirectory = app_path('Repositories/Contracts/' . implode('/', $pathParts));

        foreach ([$repositoryDirectory, $contractsDirectory] as $dir) {
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }

        $repositoryPath = "{$repositoryDirectory}/{$repositoryName}.php";
        $interfacePath = "{$contractsDirectory}/{$repositoryName}Interface.php";

        if ($this->argument('model')) {
            $fullModelClass = Str::contains($modelArg, '\\') 
                ? $modelArg 
                : "App\\Models\\{$model}";

            if (!class_exists($fullModelClass)) {
                $this->warn("The model {$fullModelClass} does not exist. The repository will be generated anyway, but you may need to adjust the model class.");
            } elseif (!is_subclass_of($fullModelClass, Model::class)) {
                $this->warn("{$fullModelClass} is not a valid Eloquent model. The repository will be generated anyway, but you may need to adjust the model class.");
            }
        }

        if (File::exists($interfacePath) && !$this->option('force')) {
            $this->warn("The interface {$repositoryName}Interface already exists.");
        } else {
            File::put($interfacePath, $this->getInterfaceContent($repositoryName, implode('\\', $pathParts)));
            $this->info("Interface {$repositoryName}Interface created successfully.");
        }

        if (File::exists($repositoryPath) && !$this->option('force')) {
            $this->warn("The repository {$repositoryName} already exists.");
        } else {
            File::put($repositoryPath, $this->getRepositoryContent($repositoryName, $model, implode('\\', $pathParts)));
            $this->info("Repository {$repositoryName} created successfully.");
        }

        if ($this->option('abstract') && !File::exists(app_path('Repositories/BaseRepository.php'))) {
            $this->generateBaseRepository();
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
    public function all(array \$columns = ['*']);
    public function find(int \$id, array \$columns = ['*']);
    public function findBy(string \$field, \$value, array \$columns = ['*']);
    public function findWhere(array \$conditions, array \$columns = ['*']);
    public function paginate(int \$perPage = 15, array \$columns = ['*']);
    public function create(array \$data);
    public function update(int \$id, array \$data);
    public function delete(int \$id);
    public function first(array \$conditions = [], array \$columns = ['*']);
}
PHP;
    }

    protected function getRepositoryContent($name, $model, $namespace)
    {
        $namespace = $namespace ? "App\\Repositories\\{$namespace}" : "App\\Repositories";
        $contractNamespace = $namespace ? str_replace("App\\Repositories", "App\\Repositories\\Contracts", $namespace) : "App\\Repositories\\Contracts";
        
        if (Str::contains($model, '\\')) {
            $modelClass = $model;
            $modelParts = explode('\\', $model);
            $model = end($modelParts);
        } else {
            $modelClass = "App\\Models\\{$model}";
        }
        
        $modelVariable = Str::camel($model);

        $useBaseRepository = File::exists(app_path('Repositories/BaseRepository.php')) ? 
            "use App\\Repositories\\BaseRepository;\n" : '';
        
        $extendsBaseRepository = File::exists(app_path('Repositories/BaseRepository.php')) ?
            "extends BaseRepository " : "";

        $constructorContent = File::exists(app_path('Repositories/BaseRepository.php')) ?
            $this->getExtendedConstructorContent($modelVariable, $model) :
            $this->getStandardConstructorContent($modelVariable, $model);

        $methodsContent = File::exists(app_path('Repositories/BaseRepository.php')) ?
            "" :
            $this->getStandardMethodsContent($modelVariable);

        return <<<PHP
<?php

namespace {$namespace};

use {$contractNamespace}\\{$name}Interface;
use {$modelClass};
{$useBaseRepository}

class {$name} {$extendsBaseRepository}implements {$name}Interface
{
    protected \${$modelVariable};

{$constructorContent}
{$methodsContent}
}
PHP;
    }

    protected function getStandardConstructorContent($modelVariable, $model)
    {
        return <<<PHP
    public function __construct({$model} \${$modelVariable})
    {
        \$this->{$modelVariable} = \${$modelVariable};
    }

PHP;
    }

    protected function getExtendedConstructorContent($modelVariable, $model)
    {
        return <<<PHP
    public function __construct({$model} \${$modelVariable})
    {
        parent::__construct(\${$modelVariable});
        \$this->{$modelVariable} = \${$modelVariable};
    }

PHP;
    }

    protected function getStandardMethodsContent($modelVariable)
    {
        return <<<PHP
    public function all(array \$columns = ['*'])
    {
        return \$this->{$modelVariable}->select(\$columns)->get();
    }
    
    public function find(int \$id, array \$columns = ['*'])
    {
        return \$this->{$modelVariable}->select(\$columns)->find(\$id);
    }
    
    public function findBy(string \$field, \$value, array \$columns = ['*'])
    {
        return \$this->{$modelVariable}->select(\$columns)->where(\$field, \$value)->first();
    }
    
    public function findWhere(array \$conditions, array \$columns = ['*'])
    {
        return \$this->{$modelVariable}->select(\$columns)->where(\$conditions)->get();
    }
    
    public function paginate(int \$perPage = 15, array \$columns = ['*'])
    {
        return \$this->{$modelVariable}->select(\$columns)->paginate(\$perPage);
    }
    
    public function create(array \$data)
    {
        return \$this->{$modelVariable}->create(\$data);
    }
    
    public function update(int \$id, array \$data)
    {
        \$model = \$this->{$modelVariable}->find(\$id);
        return \$model ? \$model->update(\$data) ? \$model : false : false;
    }
    
    public function delete(int \$id)
    {
        \$model = \$this->{$modelVariable}->find(\$id);
        return \$model ? \$model->delete() : false;
    }
    
    public function first(array \$conditions = [], array \$columns = ['*'])
    {
        \$query = \$this->{$modelVariable}->select(\$columns);
        
        if (!empty(\$conditions)) {
            \$query->where(\$conditions);
        }
        
        return \$query->first();
    }
PHP;
    }

    protected function generateBaseRepository()
    {
        $baseRepositoryDir = app_path('Repositories');
        $baseContractsDir = app_path('Repositories/Contracts');
        
        if (!File::isDirectory($baseRepositoryDir)) {
            File::makeDirectory($baseRepositoryDir, 0755, true);
        }
        
        if (!File::isDirectory($baseContractsDir)) {
            File::makeDirectory($baseContractsDir, 0755, true);
        }
        
        File::put(
            app_path('Repositories/Contracts/BaseRepositoryInterface.php'),
            $this->getBaseInterfaceContent()
        );
        
        File::put(
            app_path('Repositories/BaseRepository.php'),
            $this->getBaseRepositoryContent()
        );
        
        $this->info('BaseRepository and BaseRepositoryInterface generated successfully.');
    }
    
    protected function getBaseInterfaceContent()
    {
        return <<<PHP
<?php

namespace App\Repositories\Contracts;

interface BaseRepositoryInterface
{
    public function all(array \$columns = ['*']);
    public function find(int \$id, array \$columns = ['*']);
    public function findBy(string \$field, \$value, array \$columns = ['*']);
    public function findWhere(array \$conditions, array \$columns = ['*']);
    public function paginate(int \$perPage = 15, array \$columns = ['*']);
    public function create(array \$data);
    public function update(int \$id, array \$data);
    public function delete(int \$id);
    public function first(array \$conditions = [], array \$columns = ['*']);
}
PHP;
    }
    
    protected function getBaseRepositoryContent()
    {
        return <<<PHP
<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Repositories\Contracts\BaseRepositoryInterface;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected \$model;
    
    public function __construct(Model \$model)
    {
        \$this->model = \$model;
    }
    
    public function all(array \$columns = ['*'])
    {
        return \$this->model->select(\$columns)->get();
    }
    
    public function find(int \$id, array \$columns = ['*'])
    {
        return \$this->model->select(\$columns)->find(\$id);
    }
    
    public function findBy(string \$field, \$value, array \$columns = ['*'])
    {
        return \$this->model->select(\$columns)->where(\$field, \$value)->first();
    }
    
    public function findWhere(array \$conditions, array \$columns = ['*'])
    {
        return \$this->model->select(\$columns)->where(\$conditions)->get();
    }
    
    public function paginate(int \$perPage = 15, array \$columns = ['*'])
    {
        return \$this->model->select(\$columns)->paginate(\$perPage);
    }
    
    public function create(array \$data)
    {
        return \$this->model->create(\$data);
    }
    
    public function update(int \$id, array \$data)
    {
        \$model = \$this->model->find(\$id);
        return \$model ? \$model->update(\$data) ? \$model : false : false;
    }
    
    public function delete(int \$id)
    {
        \$model = \$this->model->find(\$id);
        return \$model ? \$model->delete() : false;
    }
    
    public function first(array \$conditions = [], array \$columns = ['*'])
    {
        \$query = \$this->model->select(\$columns);
        
        if (!empty(\$conditions)) {
            \$query->where(\$conditions);
        }
        
        return \$query->first();
    }
}
PHP;
    }
}
