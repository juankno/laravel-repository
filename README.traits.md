# Laravel Repository Traits

This document describes the traits available in the Laravel Repository package and how to leverage them to create more modular and maintainable repositories.

## Available Traits

The package includes six specialized traits that divide functionality by specific areas:

### 1. QueryableTrait

This trait handles operations related to database queries.

```php
use Juankno\Repository\Traits\QueryableTrait;

class MyRepository implements MyRepositoryInterface
{
    use QueryableTrait;
    
    // ...
}
```

#### Main Methods:

- `applyConditions($query, array $conditions)`: Efficiently applies conditions to a query
- `applyOrderBy($query, array $orderBy)`: Applies ordering to a query
- `findWhere(array $conditions, array $columns, array $relations, array $orderBy, array $scopes)`: Finds records matching the conditions
- `findBy(string $field, mixed $value, array $columns, array $relations, array $scopes)`: Finds a record by a specific field
- `first(array $conditions, array $columns, array $relations, array $orderBy, array $scopes)`: Gets the first record matching the conditions

### 2. RelationshipTrait

This trait handles Eloquent relationship loading in an optimized way.

```php
use Juankno\Repository\Traits\RelationshipTrait;

class MyRepository implements MyRepositoryInterface
{
    use RelationshipTrait;
    
    // ...
}
```

#### Main Methods:

- `loadRelations($query, array $relations)`: Loads relationships in an optimized way, including automatic detection for using withCount when appropriate

### 3. ScopableTrait

This trait allows flexible application of Eloquent scopes.

```php
use Juankno\Repository\Traits\ScopableTrait;

class MyRepository implements MyRepositoryInterface
{
    use ScopableTrait;
    
    // ...
}
```

#### Main Methods:

- `applyScopes($query, array $scopes)`: Applies scopes to a query, supporting named scopes, closures, and scopes with parameters

### 4. CrudOperationsTrait

This trait handles basic CRUD operations (Create, Read, Update, Delete).

```php
use Juankno\Repository\Traits\CrudOperationsTrait;

class MyRepository implements MyRepositoryInterface
{
    use CrudOperationsTrait;
    
    // ...
}
```

#### Main Methods:

- `all(array $columns, array $relations, array $orderBy, array $scopes)`: Gets all records
- `find(int $id, array $columns, array $relations, array $appends, array $scopes)`: Finds a record by its ID
- `create(array $data)`: Creates a new record
- `update(int $id, array $data)`: Updates an existing record
- `delete(int $id)`: Deletes a record
- `createMany(array $data)`: Creates multiple records in a single operation
- `updateWhere(array $conditions, array $data, array $scopes)`: Updates records in bulk based on conditions
- `deleteWhere(array $conditions, array $scopes)`: Deletes records in bulk based on conditions

### 5. PaginationTrait

This trait handles different types of pagination.

```php
use Juankno\Repository\Traits\PaginationTrait;

class MyRepository implements MyRepositoryInterface
{
    use PaginationTrait;
    
    // ...
}
```

#### Main Methods:

- `paginate(int $perPage, array $columns, array $relations, array $orderBy, array $conditions, array $scopes)`: Standard pagination with total count
- `simplePaginate(int $perPage, array $columns, array $relations, array $orderBy, array $conditions, array $scopes)`: Simple pagination (lighter)
- `cursorPaginate(int $perPage, array $columns, array $relations, array $orderBy, array $conditions, array $scopes)`: Cursor pagination (optimal for large datasets)

### 6. TransactionTrait

This trait handles database transactions.

```php
use Juankno\Repository\Traits\TransactionTrait;

class MyRepository implements MyRepositoryInterface
{
    use TransactionTrait;
    
    // ...
}
```

#### Main Methods:

- `beginTransaction()`: Starts a new transaction
- `commit()`: Commits the active transaction
- `rollBack()`: Rolls back the active transaction
- `transaction(callable $callback)`: Executes a callback within a transaction

## Using Multiple Traits

You can combine multiple traits in your repository to get the functionality you need:

```php
use Juankno\Repository\Traits\CrudOperationsTrait;
use Juankno\Repository\Traits\QueryableTrait;
use Juankno\Repository\Traits\PaginationTrait;

class PostRepository implements PostRepositoryInterface
{
    use CrudOperationsTrait, QueryableTrait, PaginationTrait;
    
    protected $model;
    
    public function __construct(Post $model)
    {
        $this->model = $model;
    }
    
    // Additional custom methods...
}
```

## Complete Example with All Traits

If you want to use all available traits:

```php
use Juankno\Repository\Traits\CrudOperationsTrait;
use Juankno\Repository\Traits\QueryableTrait;
use Juankno\Repository\Traits\RelationshipTrait;
use Juankno\Repository\Traits\ScopableTrait;
use Juankno\Repository\Traits\PaginationTrait;
use Juankno\Repository\Traits\TransactionTrait;

class UserRepository implements UserRepositoryInterface
{
    use CrudOperationsTrait, 
        QueryableTrait,
        RelationshipTrait,
        ScopableTrait,
        PaginationTrait,
        TransactionTrait;
    
    protected $model;
    
    public function __construct(User $model)
    {
        $this->model = $model;
    }
    
    // Custom methods...
}
```

## Configuration

You can configure the behavior of the traits through the `repository.php` configuration file:

```php
'traits' => [
    'always_include' => [
        'CrudOperationsTrait',
        'QueryableTrait',
        'RelationshipTrait',
    ],
    'optional' => [
        'ScopableTrait',
        'PaginationTrait',
        'TransactionTrait',
    ],
    'namespace' => 'Juankno\\Repository\\Traits',
],
```

This configuration specifies which traits will always be included when generating a new repository and which ones are optional.