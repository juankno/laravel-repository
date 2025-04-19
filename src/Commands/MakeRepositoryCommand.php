<?php

namespace Juankno\Repository\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:repository {name} {model?} {--force} {--abstract} {--empty : Create an empty repository without predefined methods}';
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

        // If the --empty option is active, create an empty interface
        if ($this->option('empty')) {
            return <<<PHP
<?php

namespace {$namespace};

interface {$name}Interface
{
    // Define your custom methods here
}
PHP;
        }

        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface {$name}Interface
 * @package {$namespace}
 */
interface {$name}Interface
{
    /**
     * Get all records
     * 
     * @param array \$columns Columns to select
     * @param array \$relations Relations to load
     * @param array \$orderBy Order columns [column => direction]
     * @param array \$scopes Array of scope names or callables to apply
     * @return Collection
     */
    public function all(array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): Collection;
    
    /**
     * Find a record by ID
     * 
     * @param int \$id Record ID
     * @param array \$columns Columns to select
     * @param array \$relations Relations to load
     * @param array \$appends Attributes to append
     * @param array \$scopes Array of scope names or callables to apply
     * @return Model|null
     */
    public function find(int \$id, array \$columns = ['*'], array \$relations = [], array \$appends = [], array \$scopes = []): ?Model;
    
    /**
     * Find a record by a specific field
     * 
     * @param string \$field Field to search
     * @param mixed \$value Value to search
     * @param array \$columns Columns to select
     * @param array \$relations Relations to load
     * @param array \$scopes Array of scope names or callables to apply
     * @return Model|null
     */
    public function findBy(string \$field, mixed \$value, array \$columns = ['*'], array \$relations = [], array \$scopes = []): ?Model;
    
    /**
     * Find records matching conditions
     * 
     * @param array \$conditions Conditions to search
     * @param array \$columns Columns to select
     * @param array \$relations Relations to load
     * @param array \$orderBy Order columns [column => direction]
     * @param array \$scopes Array of scope names or callables to apply
     * @return Collection
     */
    public function findWhere(array \$conditions, array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): Collection;
    
    /**
     * Paginate records
     * 
     * @param int \$perPage Records per page
     * @param array \$columns Columns to select
     * @param array \$relations Relations to load
     * @param array \$orderBy Order columns [column => direction]
     * @param array \$conditions Conditions to filter
     * @param array \$scopes Array of scope names or callables to apply
     * @return LengthAwarePaginator
     */
    public function paginate(int \$perPage = 15, array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$conditions = [], array \$scopes = []): LengthAwarePaginator;
    
    /**
     * Create a new record
     * 
     * @param array \$data Data to create record
     * @return Model|null
     */
    public function create(array \$data): ?Model;
    
    /**
     * Update an existing record
     * 
     * @param int \$id Record ID to update
     * @param array \$data Data to update
     * @return Model|bool
     */
    public function update(int \$id, array \$data): Model|bool;
    
    /**
     * Delete a record
     * 
     * @param int \$id Record ID to delete
     * @return bool
     */
    public function delete(int \$id): bool;
    
    /**
     * Get the first record matching conditions
     * 
     * @param array \$conditions Conditions to search
     * @param array \$columns Columns to select
     * @param array \$relations Relations to load
     * @param array \$orderBy Order columns [column => direction]
     * @param array \$scopes Array of scope names or callables to apply
     * @return Model|null
     */
    public function first(array \$conditions = [], array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): ?Model;
    
    /**
     * Create multiple records in a single operation
     * 
     * @param array \$data Array of data for records
     * @return Collection
     */
    public function createMany(array \$data): Collection;
    
    /**
     * Update records in bulk based on conditions
     * 
     * @param array \$conditions Conditions to filter records to update
     * @param array \$data Data to update
     * @param array \$scopes Array of scope names or callables to apply
     * @return bool
     */
    public function updateWhere(array \$conditions, array \$data, array \$scopes = []): bool;
    
    /**
     * Delete records in bulk based on conditions
     * 
     * @param array \$conditions Conditions to filter records to delete
     * @param array \$scopes Array of scope names or callables to apply
     * @return bool|int Number of records deleted or false if failed
     */
    public function deleteWhere(array \$conditions, array \$scopes = []): bool|int;
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

        // Si la opción --empty está activada, crear un repositorio vacío solo con el constructor
        if ($this->option('empty')) {
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
    // Define your custom methods here
}
PHP;
        }

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
    /**
     * Apply scopes to the query builder
     *
     * @param \$query
     * @param array \$scopes Array of scope names or callables to apply
     * @return mixed
     */
    protected function applyScopes(\$query, array \$scopes = [])
    {
        foreach (\$scopes as \$scope) {
            if (is_string(\$scope)) {
                // Apply named scope defined in the model
                \$query->\$scope();
            } elseif (is_callable(\$scope)) {
                // Apply closure scope
                \$scope(\$query);
            } elseif (is_array(\$scope) && count(\$scope) >= 1) {
                // Apply scope with parameters - first element is scope name, rest are parameters
                \$method = array_shift(\$scope);
                \$query->\$method(...\$scope);
            }
        }
        
        return \$query;
    }

    /**
     * Get all records
     */
    public function all(array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): \Illuminate\Database\Eloquent\Collection
    {
        \$query = \$this->{$modelVariable}->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        if (!empty(\$orderBy)) {
            foreach (\$orderBy as \$column => \$direction) {
                \$query->orderBy(\$column, \$direction);
            }
        }
        
        return \$query->get();
    }
    
    /**
     * Find a record by ID
     */
    public function find(int \$id, array \$columns = ['*'], array \$relations = [], array \$appends = [], array \$scopes = []): ?\Illuminate\Database\Eloquent\Model
    {
        \$query = \$this->{$modelVariable}->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        \$model = \$query->find(\$id);
        
        if (\$model && !empty(\$appends)) {
            \$model->append(\$appends);
        }
        
        return \$model;
    }
    
    /**
     * Find a record by a specific field
     */
    public function findBy(string \$field, mixed \$value, array \$columns = ['*'], array \$relations = [], array \$scopes = []): ?\Illuminate\Database\Eloquent\Model
    {
        \$query = \$this->{$modelVariable}->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        return \$query->where(\$field, \$value)->first();
    }
    
    /**
     * Find records matching conditions
     */
    public function findWhere(array \$conditions, array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): \Illuminate\Database\Eloquent\Collection
    {
        \$query = \$this->{$modelVariable}->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        foreach (\$conditions as \$field => \$value) {
            if (is_array(\$value)) {
                if (count(\$value) === 3) {
                    list(\$field, \$operator, \$searchValue) = \$value;
                    \$query->where(\$field, \$operator, \$searchValue);
                } else {
                    \$query->whereIn(\$field, \$value);
                }
            } else {
                \$query->where(\$field, \$value);
            }
        }
        
        if (!empty(\$orderBy)) {
            foreach (\$orderBy as \$column => \$direction) {
                \$query->orderBy(\$column, \$direction);
            }
        }
        
        return \$query->get();
    }
    
    /**
     * Paginate records
     */
    public function paginate(int \$perPage = 15, array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$conditions = [], array \$scopes = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        \$query = \$this->{$modelVariable}->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        if (!empty(\$conditions)) {
            foreach (\$conditions as \$field => \$value) {
                if (is_array(\$value)) {
                    if (count(\$value) === 3) {
                        list(\$field, \$operator, \$searchValue) = \$value;
                        \$query->where(\$field, \$operator, \$searchValue);
                    } else {
                        \$query->whereIn(\$field, \$value);
                    }
                } else {
                    \$query->where(\$field, \$value);
                }
            }
        }
        
        if (!empty(\$orderBy)) {
            foreach (\$orderBy as \$column => \$direction) {
                \$query->orderBy(\$column, \$direction);
            }
        }
        
        return \$query->paginate(\$perPage);
    }
    
    /**
     * Create a new record
     */
    public function create(array \$data): ?\Illuminate\Database\Eloquent\Model
    {
        return \$this->{$modelVariable}->create(\$data);
    }
    
    /**
     * Update an existing record
     */
    public function update(int \$id, array \$data): \Illuminate\Database\Eloquent\Model|bool
    {
        \$model = \$this->find(\$id);
        
        if (!\$model) {
            return false;
        }
        
        \$result = \$model->update(\$data);
        
        // Return the updated model or false if it fails
        return \$result ? \$model->fresh() : false;
    }
    
    /**
     * Delete a record
     */
    public function delete(int \$id): bool
    {
        \$model = \$this->find(\$id);
        return \$model ? \$model->delete() : false;
    }
    
    /**
     * Get the first record matching conditions
     */
    public function first(array \$conditions = [], array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): ?\Illuminate\Database\Eloquent\Model
    {
        \$query = \$this->{$modelVariable}->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        if (!empty(\$conditions)) {
            foreach (\$conditions as \$field => \$value) {
                if (is_array(\$value)) {
                    if (count(\$value) === 3) {
                        list(\$field, \$operator, \$searchValue) = \$value;
                        \$query->where(\$field, \$operator, \$searchValue);
                    } else {
                        \$query->whereIn(\$field, \$value);
                    }
                } else {
                    \$query->where(\$field, \$value);
                }
            }
        }
        
        if (!empty(\$orderBy)) {
            foreach (\$orderBy as \$column => \$direction) {
                \$query->orderBy(\$column, \$direction);
            }
        }
        
        return \$query->first();
    }
    
    /**
     * Create multiple records in a single operation
     */
    public function createMany(array \$data): \Illuminate\Database\Eloquent\Collection
    {
        \$models = collect();
        
        foreach (\$data as \$item) {
            \$models->push(\$this->create(\$item));
        }
        
        return \$models;
    }
    
    /**
     * Update records in bulk based on conditions
     */
    public function updateWhere(array \$conditions, array \$data, array \$scopes = []): bool
    {
        \$query = \$this->{$modelVariable}->query();
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        foreach (\$conditions as \$field => \$value) {
            if (is_array(\$value)) {
                if (count(\$value) === 3) {
                    list(\$field, \$operator, \$searchValue) = \$value;
                    \$query->where(\$field, \$operator, \$searchValue);
                } else {
                    \$query->whereIn(\$field, \$value);
                }
            } else {
                \$query->where(\$field, \$value);
            }
        }
        
        return \$query->update(\$data);
    }
    
    /**
     * Delete records in bulk based on conditions
     */
    public function deleteWhere(array \$conditions, array \$scopes = []): bool|int
    {
        \$query = \$this->{$modelVariable}->query();
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        foreach (\$conditions as \$field => \$value) {
            if (is_array(\$value)) {
                if (count(\$value) === 3) {
                    list(\$field, \$operator, \$searchValue) = \$value;
                    \$query->where(\$field, \$operator, \$searchValue);
                } else {
                    \$query->whereIn(\$field, \$value);
                }
            } else {
                \$query->where(\$field, \$value);
            }
        }
        
        return \$query->delete();
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

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface BaseRepositoryInterface
 * @package App\Repositories\Contracts
 */
interface BaseRepositoryInterface
{
    /**
     * Get all records
     * 
     * @param array \$columns Columns to select
     * @param array \$relations Relations to load
     * @param array \$orderBy Order columns [column => direction]
     * @param array \$scopes Array of scope names or callables to apply
     * @return Collection
     */
    public function all(array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): Collection;
    
    /**
     * Find a record by ID
     * 
     * @param int \$id Record ID
     * @param array \$columns Columns to select
     * @param array \$relations Relations to load
     * @param array \$appends Attributes to append
     * @param array \$scopes Array of scope names or callables to apply
     * @return Model|null
     */
    public function find(int \$id, array \$columns = ['*'], array \$relations = [], array \$appends = [], array \$scopes = []): ?Model;
    
    /**
     * Find a record by a specific field
     * 
     * @param string \$field Field to search
     * @param mixed \$value Value to search
     * @param array \$columns Columns to select
     * @param array \$relations Relations to load
     * @param array \$scopes Array of scope names or callables to apply
     * @return Model|null
     */
    public function findBy(string \$field, mixed \$value, array \$columns = ['*'], array \$relations = [], array \$scopes = []): ?Model;
    
    /**
     * Find records matching conditions
     * 
     * @param array \$conditions Conditions to search
     * @param array \$columns Columns to select
     * @param array \$relations Relations to load
     * @param array \$orderBy Order columns [column => direction]
     * @param array \$scopes Array of scope names or callables to apply
     * @return Collection
     */
    public function findWhere(array \$conditions, array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): Collection;
    
    /**
     * Paginate records
     * 
     * @param int \$perPage Records per page
     * @param array \$columns Columns to select
     * @param array \$relations Relations to load
     * @param array \$orderBy Order columns [column => direction]
     * @param array \$conditions Conditions to filter
     * @param array \$scopes Array of scope names or callables to apply
     * @return LengthAwarePaginator
     */
    public function paginate(int \$perPage = 15, array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$conditions = [], array \$scopes = []): LengthAwarePaginator;
    
    /**
     * Create a new record
     * 
     * @param array \$data Data to create record
     * @return Model|null
     */
    public function create(array \$data): ?Model;
    
    /**
     * Update an existing record
     * 
     * @param int \$id Record ID to update
     * @param array \$data Data to update
     * @return Model|bool
     */
    public function update(int \$id, array \$data): Model|bool;
    
    /**
     * Delete a record
     * 
     * @param int \$id Record ID to delete
     * @return bool
     */
    public function delete(int \$id): bool;
    
    /**
     * Get the first record matching conditions
     * 
     * @param array \$conditions Conditions to search
     * @param array \$columns Columns to select
     * @param array \$relations Relations to load
     * @param array \$orderBy Order columns [column => direction]
     * @param array \$scopes Array of scope names or callables to apply
     * @return Model|null
     */
    public function first(array \$conditions = [], array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): ?Model;
    
    /**
     * Create multiple records in a single operation
     * 
     * @param array \$data Array of data for records
     * @return Collection
     */
    public function createMany(array \$data): Collection;
    
    /**
     * Update records in bulk based on conditions
     * 
     * @param array \$conditions Conditions to filter records to update
     * @param array \$data Data to update
     * @param array \$scopes Array of scope names or callables to apply
     * @return bool
     */
    public function updateWhere(array \$conditions, array \$data, array \$scopes = []): bool;
    
    /**
     * Delete records in bulk based on conditions
     * 
     * @param array \$conditions Conditions to filter records to delete
     * @param array \$scopes Array of scope names or callables to apply
     * @return bool|int Number of records deleted or false if failed
     */
    public function deleteWhere(array \$conditions, array \$scopes = []): bool|int;
}
PHP;
    }
    
    protected function getBaseRepositoryContent()
    {
        return <<<PHP
<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Class BaseRepository
 * 
 * Base implementation of the Repository Pattern
 * 
 * @package App\Repositories
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var Model
     */
    protected \$model;
    
    /**
     * Constructor
     * 
     * @param Model \$model
     */
    public function __construct(Model \$model)
    {
        \$this->model = \$model;
    }
    
    /**
     * Apply scopes to the query builder
     *
     * @param \$query
     * @param array \$scopes Array of scope names or callables to apply
     * @return mixed
     */
    protected function applyScopes(\$query, array \$scopes = [])
    {
        foreach (\$scopes as \$scope) {
            if (is_string(\$scope)) {
                // Apply named scope defined in the model
                \$query->\$scope();
            } elseif (is_callable(\$scope)) {
                // Apply closure scope
                \$scope(\$query);
            } elseif (is_array(\$scope) && count(\$scope) >= 1) {
                // Apply scope with parameters - first element is scope name, rest are parameters
                \$method = array_shift(\$scope);
                \$query->\$method(...\$scope);
            }
        }
        
        return \$query;
    }
    
    /**
     * {@inheritDoc}
     */
    public function all(array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): Collection
    {
        \$query = \$this->model->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        if (!empty(\$orderBy)) {
            foreach (\$orderBy as \$column => \$direction) {
                \$query->orderBy(\$column, \$direction);
            }
        }
        
        return \$query->get();
    }
    
    /**
     * {@inheritDoc}
     */
    public function find(int \$id, array \$columns = ['*'], array \$relations = [], array \$appends = [], array \$scopes = []): ?Model
    {
        \$query = \$this->model->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        \$model = \$query->find(\$id);
        
        if (\$model && !empty(\$appends)) {
            \$model->append(\$appends);
        }
        
        return \$model;
    }
    
    /**
     * {@inheritDoc}
     */
    public function findBy(string \$field, mixed \$value, array \$columns = ['*'], array \$relations = [], array \$scopes = []): ?Model
    {
        \$query = \$this->model->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        return \$query->where(\$field, \$value)->first();
    }
    
    /**
     * {@inheritDoc}
     */
    public function findWhere(array \$conditions, array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): Collection
    {
        \$query = \$this->model->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        foreach (\$conditions as \$field => \$value) {
            if (is_array(\$value)) {
                if (count(\$value) === 3) {
                    list(\$field, \$operator, \$searchValue) = \$value;
                    \$query->where(\$field, \$operator, \$searchValue);
                } else {
                    \$query->whereIn(\$field, \$value);
                }
            } else {
                \$query->where(\$field, \$value);
            }
        }
        
        if (!empty(\$orderBy)) {
            foreach (\$orderBy as \$column => \$direction) {
                \$query->orderBy(\$column, \$direction);
            }
        }
        
        return \$query->get();
    }
    
    /**
     * {@inheritDoc}
     */
    public function paginate(int \$perPage = 15, array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$conditions = [], array \$scopes = []): LengthAwarePaginator
    {
        \$query = \$this->model->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        if (!empty(\$conditions)) {
            foreach (\$conditions as \$field => \$value) {
                if (is_array(\$value)) {
                    if (count(\$value) === 3) {
                        list(\$field, \$operator, \$searchValue) = \$value;
                        \$query->where(\$field, \$operator, \$searchValue);
                    } else {
                        \$query->whereIn(\$field, \$value);
                    }
                } else {
                    \$query->where(\$field, \$value);
                }
            }
        }
        
        if (!empty(\$orderBy)) {
            foreach (\$orderBy as \$column => \$direction) {
                \$query->orderBy(\$column, \$direction);
            }
        }
        
        return \$query->paginate(\$perPage);
    }
    
    /**
     * {@inheritDoc}
     */
    public function create(array \$data): ?Model
    {
        return \$this->model->create(\$data);
    }
    
    /**
     * {@inheritDoc}
     */
    public function update(int \$id, array \$data): Model|bool
    {
        \$model = \$this->find(\$id);
        
        if (!\$model) {
            return false;
        }
        
        \$result = \$model->update(\$data);
        
        // Return the updated model or false if it fails
        return \$result ? \$model->fresh() : false;
    }
    
    /**
     * {@inheritDoc}
     */
    public function delete(int \$id): bool
    {
        return \$this->find(\$id)?->delete() ?? false;
    }
    
    /**
     * {@inheritDoc}
     */
    public function first(array \$conditions = [], array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): ?Model
    {
        \$query = \$this->model->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        if (!empty(\$conditions)) {
            foreach (\$conditions as \$field => \$value) {
                if (is_array(\$value)) {
                    if (count(\$value) === 3) {
                        list(\$field, \$operator, \$searchValue) = \$value;
                        \$query->where(\$field, \$operator, \$searchValue);
                    } else {
                        \$query->whereIn(\$field, \$value);
                    }
                } else {
                    \$query->where(\$field, \$value);
                }
            }
        }
        
        if (!empty(\$orderBy)) {
            foreach (\$orderBy as \$column => \$direction) {
                \$query->orderBy(\$column, \$direction);
            }
        }
        
        return \$query->first();
    }
    
    /**
     * {@inheritDoc}
     */
    public function createMany(array \$data): Collection
    {
        \$models = collect();
        
        foreach (\$data as \$item) {
            \$models->push(\$this->create(\$item));
        }
        
        return \$models;
    }
    
    /**
     * {@inheritDoc}
     */
    public function updateWhere(array \$conditions, array \$data, array \$scopes = []): bool
    {
        \$query = \$this->model->query();
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        foreach (\$conditions as \$field => \$value) {
            if (is_array(\$value)) {
                if (count(\$value) === 3) {
                    list(\$field, \$operator, \$searchValue) = \$value;
                    \$query->where(\$field, \$operator, \$searchValue);
                } else {
                    \$query->whereIn(\$field, \$value);
                }
            } else {
                \$query->where(\$field, \$value);
            }
        }
        
        return \$query->update(\$data);
    }
    
    /**
     * {@inheritDoc}
     */
    public function deleteWhere(array \$conditions, array \$scopes = []): bool|int
    {
        \$query = \$this->model->query();
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        foreach (\$conditions as \$field => \$value) {
            if (is_array(\$value)) {
                if (count(\$value) === 3) {
                    list(\$field, \$operator, \$searchValue) = \$value;
                    \$query->where(\$field, \$operator, \$searchValue);
                } else {
                    \$query->whereIn(\$field, \$value);
                }
            } else {
                \$query->where(\$field, \$value);
            }
        }
        
        return \$query->delete();
    }
}
PHP;
    }
}
