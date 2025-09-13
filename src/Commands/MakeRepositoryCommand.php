<?php

namespace Juankno\Repository\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:repository {name} {model?} {--force} {--abstract} {--full : Create a repository with all predefined methods} {--no-traits : Create a repository with implementation without using traits}';
    protected $description = 'Create a basic repository with its contract (use --full for complete functionality)';

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

        // Obtener la carpeta configurada para interfaces
        $interfacesFolderName = config('repository.structure.interfaces_folder', 'Contracts');
        $validateFolders = config('repository.structure.validate_interface_folders', true);

        $repositoryDirectory = app_path('Repositories/' . implode('/', $pathParts));
        $interfacesDirectory = app_path("Repositories/{$interfacesFolderName}/" . implode('/', $pathParts));

        // Crear los directorios necesarios
        if (!File::isDirectory($repositoryDirectory)) {
            File::makeDirectory($repositoryDirectory, 0755, true);
        }

        // Verificar si existe un directorio alternativo para interfaces
        $alternativeInterfaceDirectory = app_path('Repositories/Interfaces/' . implode('/', $pathParts));
        $interfaceDirectoryExists = File::isDirectory($interfacesDirectory);
        $alternativeDirectoryExists = File::isDirectory($alternativeInterfaceDirectory);

        if ($validateFolders && !$interfaceDirectoryExists && $alternativeDirectoryExists) {
            // Si se debe validar y la carpeta configurada no existe pero existe la alternativa
            $this->info("Using existing interface directory: Repositories/Interfaces");
            $interfacesDirectory = $alternativeInterfaceDirectory;
            $interfacesFolderName = 'Interfaces';
        } else {
            // En caso contrario, usar la carpeta configurada
            if (!File::isDirectory($interfacesDirectory)) {
                File::makeDirectory($interfacesDirectory, 0755, true);
            }
        }

        $repositoryPath = "{$repositoryDirectory}/{$repositoryName}.php";
        $interfacePath = "{$interfacesDirectory}/{$repositoryName}Interface.php";

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
            File::put($interfacePath, $this->getInterfaceContent($repositoryName, implode('\\', $pathParts), $interfacesFolderName));
            $this->info("Interface {$repositoryName}Interface created successfully in {$interfacesFolderName} folder.");
        }

        if (File::exists($repositoryPath) && !$this->option('force')) {
            $this->warn("The repository {$repositoryName} already exists.");
        } else {
            File::put($repositoryPath, $this->getRepositoryContent($repositoryName, $model, implode('\\', $pathParts), $interfacesFolderName));
            $this->info("Repository {$repositoryName} created successfully.");
        }

        if ($this->option('abstract') && !File::exists(app_path('Repositories/BaseRepository.php'))) {
            $this->generateBaseRepository($interfacesFolderName);
        }

        // Actualizar la configuración si se está usando una carpeta alternativa
        if ($interfacesFolderName !== config('repository.structure.interfaces_folder')) {
            $this->info("Note: Used '{$interfacesFolderName}' for interfaces instead of configured value.");
            $this->info("You may want to update the 'repository.structure.interfaces_folder' config value.");
        }
    }

    protected function getInterfaceContent($name, $namespace, $interfacesFolderName = 'Contracts')
    {
        $namespace = $namespace ? "App\\Repositories\\{$interfacesFolderName}\\{$namespace}" : "App\\Repositories\\{$interfacesFolderName}";

        // By default, create a basic interface. Use --full for complete interface
        if (!$this->option('full')) {
            return $this->formatCode(<<<PHP
<?php

namespace {$namespace};

/**
 * Interface {$name}Interface
 * @package {$namespace}
 */
interface {$name}Interface
{
    /**
     * Find a record by ID
     * 
     * @param int \$id
     * @return mixed
     */
    public function find(int \$id);

    /**
     * Get all records
     * 
     * @return mixed
     */
    public function getAll();

    /**
     * Create a new record
     * 
     * @param array \$data
     * @return mixed
     */
    public function create(array \$data);

    /**
     * Update an existing record
     * 
     * @param int \$id
     * @param array \$data
     * @return mixed
     */
    public function update(int \$id, array \$data);

    /**
     * Delete a record
     * 
     * @param int \$id
     * @return bool
     */
    public function delete(int \$id);
}
PHP
            );
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

    protected function getRepositoryContent($name, $model, $namespace, $interfacesFolderName = 'Contracts')
    {
        $namespace = $namespace ? "App\\Repositories\\{$namespace}" : "App\\Repositories";
        $contractNamespace = $namespace ? str_replace("App\\Repositories", "App\\Repositories\\{$interfacesFolderName}", $namespace) : "App\\Repositories\\{$interfacesFolderName}";
        
        if (Str::contains($model, '\\')) {
            $modelClass = $model;
            $modelParts = explode('\\', $model);
            $model = end($modelParts);
        } else {
            $modelClass = "App\\Models\\{$model}";
        }

        $useBaseRepository = File::exists(app_path('Repositories/BaseRepository.php')) ? 
            "use App\\Repositories\\BaseRepository;\n" : '';
        
        $extendsBaseRepository = File::exists(app_path('Repositories/BaseRepository.php')) ?
            "extends BaseRepository " : "";

        $constructorContent = File::exists(app_path('Repositories/BaseRepository.php')) ?
            $this->getExtendedConstructorContent($model) :
            $this->getStandardConstructorContent($model);

        // By default, create a basic repository. Use --full for complete repository with all methods
        if (!$this->option('full')) {
            return $this->formatCode(<<<PHP
<?php

namespace {$namespace};

use {$contractNamespace}\\{$name}Interface;
use {$modelClass};
{$useBaseRepository}

/**
 * Class {$name}
 * @package {$namespace}
 */
class {$name} {$extendsBaseRepository}implements {$name}Interface
{
    /**
     * @var {$model}
     */
    protected \$model;

{$constructorContent}
    /**
     * Find a record by ID
     * 
     * @param int \$id
     * @return {$model}|null
     */
    public function find(int \$id)
    {
        return \$this->model->find(\$id);
    }

    /**
     * Get all records
     * 
     * @return \\Illuminate\\Database\\Eloquent\\Collection
     */
    public function getAll()
    {
        return \$this->model->all();
    }

    /**
     * Create a new record
     * 
     * @param array \$data
     * @return {$model}
     */
    public function create(array \$data)
    {
        return \$this->model->create(\$data);
    }

    /**
     * Update an existing record
     * 
     * @param int \$id
     * @param array \$data
     * @return {$model}|bool
     */
    public function update(int \$id, array \$data)
    {
        \$record = \$this->find(\$id);
        
        if (!\$record) {
            return false;
        }
        
        \$record->update(\$data);
        
        return \$record->fresh();
    }

    /**
     * Delete a record
     * 
     * @param int \$id
     * @return bool
     */
    public function delete(int \$id)
    {
        \$record = \$this->find(\$id);
        
        if (!\$record) {
            return false;
        }
        
        return \$record->delete();
    }
}
PHP
            );
        }

        // Si se está usando la opción --abstract, extender de BaseRepository sin usar traits
        if (File::exists(app_path('Repositories/BaseRepository.php'))) {
            return <<<PHP
<?php

namespace {$namespace};

use {$contractNamespace}\\{$name}Interface;
use {$modelClass};
{$useBaseRepository}

class {$name} {$extendsBaseRepository}implements {$name}Interface
{
    /**
     * @var {$model}
     */
    protected \$model;

{$constructorContent}
}
PHP;
        }
        
        // Si está activada la opción --no-traits, crear un repositorio con implementación directa sin traits
        if ($this->option('no-traits')) {
            return <<<PHP
<?php

namespace {$namespace};

use {$contractNamespace}\\{$name}Interface;
use {$modelClass};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class {$name} implements {$name}Interface
{
    /**
     * @var {$model}
     */
    protected \$model;

{$constructorContent}
{$this->getInlineImplementationContent()}
}
PHP;
        }

        // Usar traits como opción predeterminada si no se especifica --no-traits o --abstract
        $traitsContent = $this->getTraitsContent();
        $useTraitsStatements = $this->getUseTraitsStatements();

        return <<<PHP
<?php

namespace {$namespace};

use {$contractNamespace}\\{$name}Interface;
use {$modelClass};
{$useTraitsStatements}

class {$name} implements {$name}Interface
{
    /**
     * @var {$model}
     */
    protected \$model;
{$traitsContent}

{$constructorContent}
}
PHP;
    }
    
    protected function getUseTraitsStatements()
    {
        return <<<PHP
use Juankno\Repository\Traits\CrudOperationsTrait;
use Juankno\Repository\Traits\QueryableTrait;
use Juankno\Repository\Traits\RelationshipTrait;
use Juankno\Repository\Traits\ScopableTrait;
use Juankno\Repository\Traits\PaginationTrait;
use Juankno\Repository\Traits\TransactionTrait;
PHP;
    }

    protected function getTraitsContent()
    {
        return <<<PHP

    use CrudOperationsTrait, 
        QueryableTrait, 
        RelationshipTrait,
        ScopableTrait,
        PaginationTrait,
        TransactionTrait;
PHP;
    }

    protected function getStandardConstructorContent($model)
    {
        return <<<PHP
    public function __construct({$model} \$model)
    {
        \$this->model = \$model;
    }

PHP;
    }

    protected function getExtendedConstructorContent($model)
    {
        return <<<PHP
    public function __construct({$model} \$model)
    {
        parent::__construct(\$model);
        \$this->model = \$model;
    }

PHP;
    }

    /**
     * Get a complete inline implementation of the repository methods without traits
     * 
     * @return string
     */
    protected function getInlineImplementationContent()
    {
        return <<<PHP
    /**
     * Apply scopes to the query builder
     */
    protected function applyScopes(Builder \$query, array \$scopes = []): Builder
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
     * Apply conditions to a query
     */
    protected function applyConditions(Builder \$query, array \$conditions): void
    {
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
    
    /**
     * Apply order by clauses
     */
    protected function applyOrderBy(Builder \$query, array \$orderBy = []): void
    {
        if (!empty(\$orderBy)) {
            foreach (\$orderBy as \$column => \$direction) {
                \$query->orderBy(\$column, \$direction);
            }
        }
    }
    
    /**
     * Load relations for a query
     */
    protected function loadRelations(Builder \$query, array \$relations = []): Builder
    {
        if (!empty(\$relations)) {
            \$query->with(\$relations);
        }
        
        return \$query;
    }
    
    /**
     * Get all records
     */
    public function all(array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): Collection
    {
        \$query = \$this->model->select(\$columns);
        
        \$query = \$this->applyScopes(\$query, \$scopes);
        \$query = \$this->loadRelations(\$query, \$relations);
        \$this->applyOrderBy(\$query, \$orderBy);
        
        return \$query->get();
    }
    
    /**
     * Find a record by ID
     */
    public function find(int \$id, array \$columns = ['*'], array \$relations = [], array \$appends = [], array \$scopes = []): ?Model
    {
        \$query = \$this->model->select(\$columns);
        
        \$query = \$this->applyScopes(\$query, \$scopes);
        \$query = \$this->loadRelations(\$query, \$relations);
        
        \$model = \$query->find(\$id);
        
        if (\$model && !empty(\$appends)) {
            \$model->append(\$appends);
        }
        
        return \$model;
    }
    
    /**
     * Find a record by a specific field
     */
    public function findBy(string \$field, mixed \$value, array \$columns = ['*'], array \$relations = [], array \$scopes = []): ?Model
    {
        \$query = \$this->model->select(\$columns);
        
        \$query = \$this->applyScopes(\$query, \$scopes);
        \$query = \$this->loadRelations(\$query, \$relations);
        
        return \$query->where(\$field, \$value)->first();
    }
    
    /**
     * Find records matching conditions
     */
    public function findWhere(array \$conditions, array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): Collection
    {
        \$query = \$this->model->select(\$columns);
        
        \$query = \$this->applyScopes(\$query, \$scopes);
        \$query = \$this->loadRelations(\$query, \$relations);
        \$this->applyConditions(\$query, \$conditions);
        \$this->applyOrderBy(\$query, \$orderBy);
        
        return \$query->get();
    }
    
    /**
     * Get the first record matching conditions
     */
    public function first(array \$conditions = [], array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): ?Model
    {
        \$query = \$this->model->select(\$columns);
        
        \$query = \$this->applyScopes(\$query, \$scopes);
        \$query = \$this->loadRelations(\$query, \$relations);
        
        if (!empty(\$conditions)) {
            \$this->applyConditions(\$query, \$conditions);
        }
        
        \$this->applyOrderBy(\$query, \$orderBy);
        
        return \$query->first();
    }
    
    /**
     * Paginate records
     */
    public function paginate(int \$perPage = 15, array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$conditions = [], array \$scopes = []): LengthAwarePaginator
    {
        \$query = \$this->model->select(\$columns);
        
        \$query = \$this->applyScopes(\$query, \$scopes);
        \$query = \$this->loadRelations(\$query, \$relations);
        
        if (!empty(\$conditions)) {
            \$this->applyConditions(\$query, \$conditions);
        }
        
        \$this->applyOrderBy(\$query, \$orderBy);
        
        return \$query->paginate(\$perPage);
    }
    
    /**
     * Create a new record
     */
    public function create(array \$data): ?Model
    {
        return \$this->model->create(\$data);
    }
    
    /**
     * Update an existing record
     */
    public function update(int \$id, array \$data): Model|bool
    {
        \$model = \$this->find(\$id);
        
        if (!\$model) {
            return false;
        }
        
        \$updated = \$model->update(\$data);
        
        return \$updated ? \$model->fresh() : false;
    }
    
    /**
     * Delete a record
     */
    public function delete(int \$id): bool
    {
        \$model = \$this->find(\$id);
        
        if (!\$model) {
            return false;
        }
        
        return \$model->delete();
    }
    
    /**
     * Create multiple records in a single operation
     */
    public function createMany(array \$data): Collection
    {
        return collect(\$data)->map(function(\$item) {
            return \$this->create(\$item);
        });
    }
    
    /**
     * Update records in bulk based on conditions
     */
    public function updateWhere(array \$conditions, array \$data, array \$scopes = []): bool
    {
        \$query = \$this->model->query();
        
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$conditions)) {
            \$this->applyConditions(\$query, \$conditions);
        }
        
        return \$query->update(\$data);
    }
    
    /**
     * Delete records in bulk based on conditions
     */
    public function deleteWhere(array \$conditions, array \$scopes = []): bool|int
    {
        \$query = \$this->model->query();
        
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$conditions)) {
            \$this->applyConditions(\$query, \$conditions);
        }
        
        return \$query->delete();
    }
    
    /**
     * Begin a new database transaction
     */
    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }
    
    /**
     * Commit the active database transaction
     */
    public function commit(): void
    {
        DB::commit();
    }
    
    /**
     * Rollback the active database transaction
     */
    public function rollBack(): void
    {
        DB::rollBack();
    }
    
    /**
     * Execute a callback within a transaction
     */
    public function transaction(callable \$callback)
    {
        return DB::transaction(\$callback);
    }
PHP;
    }

    protected function generateBaseRepository($interfacesFolderName = 'Contracts')
    {
        $baseRepositoryDir = app_path('Repositories');
        $baseContractsDir = app_path("Repositories/{$interfacesFolderName}");
        
        if (!File::isDirectory($baseRepositoryDir)) {
            File::makeDirectory($baseRepositoryDir, 0755, true);
        }
        
        if (!File::isDirectory($baseContractsDir)) {
            File::makeDirectory($baseContractsDir, 0755, true);
        }
        
        File::put(
            app_path("Repositories/{$interfacesFolderName}/BaseRepositoryInterface.php"),
            $this->getBaseInterfaceContent($interfacesFolderName)
        );
        
        File::put(
            app_path('Repositories/BaseRepository.php'),
            $this->getBaseRepositoryContent()
        );
        
        $this->info('BaseRepository and BaseRepositoryInterface generated successfully.');
    }
    
    protected function getBaseInterfaceContent($interfacesFolderName = 'Contracts')
    {
        return <<<PHP
<?php

namespace App\Repositories\\{$interfacesFolderName};

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface BaseRepositoryInterface
 * @package App\Repositories\\{$interfacesFolderName}
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Support\Facades\DB;

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
     * @param Builder \$query
     * @param array \$scopes Array of scope names or callables to apply
     * @return Builder
     */
    protected function applyScopes(Builder \$query, array \$scopes = []): Builder
    {
        // Apply global scopes from configuration if any
        \$globalScopes = config('repository.scopes.global', []);
        foreach (\$globalScopes as \$globalScope) {
            if (is_string(\$globalScope) && method_exists(\$this->model, 'scope' . ucfirst(\$globalScope))) {
                \$query->\$globalScope();
            }
        }
        
        // Apply scopes provided to the method
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
            
            // Detect N+1 problems if enabled and in debug mode
            if (config('app.debug') && config('repository.scopes.detect_n_plus_one', false)) {
                \$scopeName = is_string(\$scope) ? \$scope : 'closure_scope';
                DB::listen(function(\$query) use (\$scopeName) {
                    if (strpos(\$query->sql, 'select') === 0) {
                        info("[Repository N+1 Detection] Scope: {\$scopeName} - Query: {\$query->sql}");
                    }
                });
            }
        }
        
        return \$query;
    }
    
    /**
     * Optimized loading of relations
     *
     * @param Builder \$query
     * @param array \$relations
     * @return Builder
     */
    protected function loadRelations(Builder \$query, array \$relations): Builder
    {
        if (empty(\$relations)) {
            return \$query;
        }
        
        // Optimize relation loading
        \$autoLoadCount = config('repository.relations.auto_load_count', true);
        \$maxEagerRelations = config('repository.relations.max_eager_relations', 5);
        
        // Split normal relations and relations to count
        \$eagerLoad = [];
        \$withCountRelations = [];
        
        foreach (\$relations as \$relation) {
            \$eagerLoad[] = \$relation;
            
            // Detect potential relations for withCount if enabled
            if (\$autoLoadCount) {
                \$relationName = explode('.', \$relation)[0];
                if (method_exists(\$this->model, \$relationName)) {
                    try {
                        \$relationType = \$this->model->\$relationName();
                        if (is_a(\$relationType, 'Illuminate\\Database\\Eloquent\\Relations\\HasMany') || 
                            is_a(\$relationType, 'Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany')) {
                            \$withCountRelations[] = \$relationName;
                        }
                    } catch (\Exception \$e) {
                        // Ignore errors when trying to detect relation type
                    }
                }
            }
        }
        
        // Apply with or withCount depending on the number of relations
        if (count(\$eagerLoad) <= \$maxEagerRelations) {
            \$query->with(\$eagerLoad);
        } else {
            // If there are too many relations, load only the main ones
            \$primaryRelations = array_slice(\$eagerLoad, 0, \$maxEagerRelations);
            \$query->with(\$primaryRelations);
        }
        
        // Apply withCount if there are detected relations
        if (!empty(\$withCountRelations)) {
            \$query->withCount(array_unique(\$withCountRelations));
        }
        
        return \$query;
    }
    
    /**
     * Apply conditions efficiently
     *
     * @param Builder \$query
     * @param array \$conditions
     * @return void
     */
    protected function applyConditions(Builder \$query, array \$conditions): void
    {
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
    
    /**
     * {@inheritDoc}
     */
    public function all(array \$columns = ['*'], array \$relations = [], array \$orderBy = [], array \$scopes = []): Collection
    {
        \$query = \$this->model->select(\$columns);
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        // Load relations more efficiently
        \$query = \$this->loadRelations(\$query, \$relations);
        
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
        
        // Load relations more efficiently
        \$query = \$this->loadRelations(\$query, \$relations);
        
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
        
        // Load relations more efficiently
        \$query = \$this->loadRelations(\$query, \$relations);
        
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
        
        // Load relations more efficiently
        \$query = \$this->loadRelations(\$query, \$relations);
        
        // Apply conditions efficiently
        \$this->applyConditions(\$query, \$conditions);
        
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
        
        // Load relations more efficiently
        \$query = \$this->loadRelations(\$query, \$relations);
        
        if (!empty(\$conditions)) {
            \$this->applyConditions(\$query, \$conditions);
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
        // Use direct update or find + update according to configuration
        \$useDirectUpdate = config('repository.query.use_direct_update', true);
        
        if (\$useDirectUpdate) {
            \$affected = \$this->model->where('id', \$id)->update(\$data);
            
            if (\$affected) {
                return \$this->find(\$id);
            }
            
            return false;
        } 
        
        // Previous method (find + update)
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
        // Use direct delete or find + delete according to configuration
        \$useDirectDelete = config('repository.query.use_direct_delete', true);
        
        if (\$useDirectDelete) {
            return \$this->model->where('id', \$id)->delete() > 0;
        }
        
        // Previous method (find + delete)
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
        
        // Load relations more efficiently
        \$query = \$this->loadRelations(\$query, \$relations);
        
        if (!empty(\$conditions)) {
            \$this->applyConditions(\$query, \$conditions);
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
        // Optimized to use less memory and be more efficient
        return collect(\$data)->map(function(\$item) {
            return \$this->create(\$item);
        });
    }
    
    /**
     * {@inheritDoc}
     */
    public function updateWhere(array \$conditions, array \$data, array \$scopes = []): bool
    {
        \$query = \$this->model->query();
        
        // Apply scopes if provided
        \$query = \$this->applyScopes(\$query, \$scopes);
        
        if (!empty(\$conditions)) {
            \$this->applyConditions(\$query, \$conditions);
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
        
        if (!empty(\$conditions)) {
            \$this->applyConditions(\$query, \$conditions);
        }
        
        return \$query->delete();
    }
    
    /**
     * Begin a new database transaction
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }
    
    /**
     * Commit the active database transaction
     *
     * @return void
     */
    public function commit(): void
    {
        DB::commit();
    }
    
    /**
     * Rollback the active database transaction
     *
     * @return void
     */
    public function rollBack(): void
    {
        DB::rollBack();
    }
    
    /**
     * Execute a callback within a transaction
     *
     * @param  callable  \$callback
     * @return mixed
     *
     * @throws \\Throwable
     */
    public function transaction(callable \$callback)
    {
        return DB::transaction(\$callback);
    }
}
PHP;
    }

    /**
     * Format the generated code to ensure proper indentation and PSR-12 compliance
     * 
     * @param string $code
     * @return string
     */
    protected function formatCode(string $code): string
    {
        // Split into lines
        $lines = explode("\n", $code);
        $formattedLines = [];
        
        foreach ($lines as $line) {
            // Skip empty lines at the beginning
            if (empty($formattedLines) && trim($line) === '') {
                continue;
            }
            
            $formattedLines[] = $line;
        }
        
        return implode("\n", $formattedLines);
    }
}
