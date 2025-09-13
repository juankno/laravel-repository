# Laravel Repository

🌎 Read this in [Spanish](README.es.md).

This package simplifies working with the **Repository Pattern** in Laravel by automatically generating repository files, contracts, and bindings.

## 🆕 **New in v1.7.0** - Simple by Default

The command behavior has been improved to follow the **principle of least surprise**:

- **Default**: Creates a basic repository with essential CRUD methods (find, getAll, create, update, delete)
- **Advanced**: Use `--full` flag to get all advanced features (filtering, scoping, bulk operations, etc.)

This makes the package more beginner-friendly while keeping all the power for advanced users.

## Installation

Install the package using Composer:

```sh
composer require juankno/laravel-repository
```

## Configuration

If Laravel does not auto-discover the package, manually register the `RepositoryServiceProvider` in `config/app.php`:

```php
'providers' => [
    Juankno\Repository\Providers\RepositoryServiceProvider::class,
],
```

## Publishing the Repository Service Provider

If you want to customize the `RepositoryServiceProvider`, you can publish it using:

```sh
php artisan vendor:publish --tag=repository-provider
```

## Usage

### Creating a Repository

To generate a basic repository (recommended), run the following Artisan command:

```sh
php artisan make:repository UserRepository
```

This creates a simple repository with basic CRUD operations (find, getAll, create, update, delete).

If you want to associate it with a specific model:

```sh
php artisan make:repository UserRepository User
```

For a repository with **all advanced methods and features**, use the `--full` option:

```sh
php artisan make:repository UserRepository User --full
```

### Generating a Base Repository

You can generate an abstract `BaseRepository` class along with its interface to avoid code duplication:

```sh
php artisan make:repository UserRepository User --abstract
```

This will create a `BaseRepository` and `BaseRepositoryInterface` in your application, which other repositories can extend.

### Creating a Full-Featured Repository

If you need all advanced features and methods, use the `--full` option:

```sh
php artisan make:repository UserRepository --full
```

This creates a repository with all available methods including advanced filtering, scoping, bulk operations, and more.

## Available Commands

### `make:repository`

This command generates a repository along with its contract and implementation.

#### **Usage:**
```sh
php artisan make:repository {name} {model?} {--force} {--abstract} {--full} {--no-traits}
```

#### **Arguments:**
- `name` _(required)_: The name of the repository.
- `model` _(optional)_: The associated Eloquent model.

#### **Options:**
- `--force`: Overwrite existing files if they already exist.
- `--abstract`: Generate a BaseRepository and BaseRepositoryInterface.
- `--full`: Create a repository with all advanced methods and features.
- `--no-traits`: Create a repository with implementation without using traits.

#### **Examples:**

```sh
# Create a basic repository (recommended for most cases)
php artisan make:repository UserRepository User

# Create a repository with all advanced methods
php artisan make:repository UserRepository User --full

# Create a repository in a subfolder
php artisan make:repository Admin/UserRepository User

# Create a repository and generate BaseRepository
php artisan make:repository UserRepository User --abstract

# Force overwrite of existing files
php artisan make:repository UserRepository User --force

# Create repository without traits (direct implementation)
php artisan make:repository UserRepository User --no-traits
```

## Available Repository Methods

### Basic Repository Methods (Default)

Each basic repository includes these essential CRUD methods:

- `find(int $id)`: Find a record by ID.
- `getAll()`: Get all records.
- `create(array $data)`: Create a new record.
- `update(int $id, array $data)`: Update a record.
- `delete(int $id)`: Delete a record.

### Full Repository Methods (--full option)

When using the `--full` option, repositories include all these advanced methods:

- `all(array $columns = ['*'], array $relations = [], array $orderBy = [])`: Get all records.
- `find(int $id, array $columns = ['*'], array $relations = [], array $appends = [])`: Find a record by ID.
- `findBy(string $field, $value, array $columns = ['*'], array $relations = [])`: Find a record by a specific field.
- `findWhere(array $conditions, array $columns = ['*'], array $relations = [], array $orderBy = [])`: Find records matching conditions.
- `paginate(int $perPage = 15, array $columns = ['*'], array $relations = [], array $orderBy = [], array $conditions = [])`: Paginate records.
- `create(array $data)`: Create a new record.
- `update(int $id, array $data)`: Update a record.
- `delete(int $id)`: Delete a record.
- `first(array $conditions = [], array $columns = ['*'], array $relations = [], array $orderBy = [])`: Get the first record matching conditions.
- `createMany(array $data)`: Create multiple records in a single operation.
- `updateWhere(array $conditions, array $data)`: Update multiple records based on conditions.
- `deleteWhere(array $conditions)`: Delete multiple records based on conditions.

## Detailed Method Examples

### Retrieving All Records

```php
// Get all users
$users = $userRepository->all();

// Get specific columns
$userNames = $userRepository->all(['id', 'name', 'email']);

// Get records with relations
$usersWithPosts = $userRepository->all(['*'], ['posts']);

// Get records with custom ordering
$usersByNewest = $userRepository->all(['*'], [], ['created_at' => 'desc']);

// Get records with multiple relations and ordering
$users = $userRepository->all(
    ['*'],
    ['posts', 'profile', 'roles'],
    ['name' => 'asc']
);
```

### Finding Records by ID

```php
// Find user by ID
$user = $userRepository->find(1);

// Find user with specific columns
$user = $userRepository->find(1, ['id', 'name', 'email']);

// Find user and load relations
$userWithPosts = $userRepository->find(1, ['*'], ['posts']);

// Find user with appended attributes
$userWithFullName = $userRepository->find(1, ['*'], [], ['full_name']);

// Find user with relations and appended attributes
$user = $userRepository->find(
    1,
    ['*'],
    ['posts', 'comments'],
    ['full_name', 'post_count']
);
```

### Finding Records by a Specific Field

```php
// Find user by email
$user = $userRepository->findBy('email', 'john@example.com');

// Find user by username with specific columns
$user = $userRepository->findBy('username', 'johndoe', ['id', 'username', 'email']);

// Find user with relations
$user = $userRepository->findBy('email', 'john@example.com', ['*'], ['posts', 'profile']);
```

### Finding Records with Conditions

```php
// Find active users
$activeUsers = $userRepository->findWhere(['status' => 'active']);

// Find users with specific role
$adminUsers = $userRepository->findWhere(['role' => 'admin'], ['id', 'name', 'email']);

// Using operators in conditions
$recentUsers = $userRepository->findWhere([
    ['created_at', '>=', now()->subDays(7)]
]);

// Find users with multiple conditions and load relations
$users = $userRepository->findWhere(
    [
        'status' => 'active',
        ['age', '>', 18]
    ],
    ['*'],
    ['posts', 'profile']
);

// Find users with specific IDs (whereIn)
$specificUsers = $userRepository->findWhere(['id' => [1, 2, 3]]);

// Find with custom ordering
$users = $userRepository->findWhere(
    ['status' => 'active'],
    ['*'],
    ['profile'],
    ['name' => 'asc']
);
```

### Paginating Records

```php
// Paginate users (15 per page by default)
$paginatedUsers = $userRepository->paginate();

// Custom pagination
$paginatedUsers = $userRepository->paginate(25);

// Paginate with specific columns
$paginatedUsers = $userRepository->paginate(10, ['id', 'name', 'email']);

// Paginate and load relations
$paginatedUsers = $userRepository->paginate(20, ['*'], ['posts']);

// Paginate with conditions
$paginatedActiveUsers = $userRepository->paginate(
    15,
    ['*'],
    [],
    ['created_at' => 'desc'],
    ['status' => 'active']
);

// Display paginated results in a view
return view('users.index', compact('paginatedUsers'));
```

### Creating Records

```php
// Create a new user
$userData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password')
];
$user = $userRepository->create($userData);

// Create and use immediately
$post = $postRepository->create([
    'title' => 'New Post',
    'content' => 'Post content',
    'user_id' => $user->id
]);
```

### Creating Multiple Records

```php
// Create multiple users at once
$usersData = [
    [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('password')
    ],
    [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'password' => bcrypt('password')
    ]
];

$users = $userRepository->createMany($usersData);

// Access the created models
foreach ($users as $user) {
    echo $user->id . ': ' . $user->name . "\n";
}
```

### Updating Records

```php
// Update a user
$updatedUser = $userRepository->update(1, [
    'name' => 'Updated Name',
    'email' => 'updated@example.com'
]);

// Check if update was successful
if ($updatedUser) {
    // Update successful, $updatedUser contains the fresh model instance
} else {
    // Update failed or user not found
}
```

### Updating Records in Bulk

```php
// Update all active users to have a verified status
$updated = $userRepository->updateWhere(
    ['status' => 'active'],
    ['is_verified' => true]
);

// Update users with specific role and created before a certain date
$updated = $userRepository->updateWhere(
    [
        'role' => 'customer',
        ['created_at', '<', now()->subYear()]
    ],
    [
        'status' => 'inactive',
        'needs_verification' => true
    ]
);
```

### Deleting Records

```php
// Delete a user
$deleted = $userRepository->delete(1);

// Check if deletion was successful
if ($deleted) {
    // User was successfully deleted
} else {
    // Deletion failed or user not found
}
```

### Deleting Multiple Records

```php
// Delete inactive users
$deleted = $userRepository->deleteWhere(['status' => 'inactive']);

// Delete users that haven't logged in for a year
$deleted = $userRepository->deleteWhere([
    ['last_login_at', '<', now()->subYear()]
]);

// Delete users with specific roles
$deleted = $userRepository->deleteWhere([
    'role' => ['guest', 'inactive', 'blocked']
]);

// The return value is the number of deleted records
echo "Deleted {$deleted} records";
```

### Getting the First Matching Record

```php
// Get the first active admin user
$admin = $userRepository->first(['role' => 'admin', 'status' => 'active']);

// Get first with specific columns
$user = $userRepository->first(
    ['status' => 'active'],
    ['id', 'name', 'email']
);

// Get first with relations
$user = $userRepository->first(
    ['role' => 'editor'],
    ['*'],
    ['posts', 'profile']
);

// Get first with complex conditions and custom ordering
$user = $userRepository->first(
    [
        'status' => 'active',
        ['subscription_ends_at', '>', now()]
    ],
    ['*'],
    ['subscription'],
    ['created_at' => 'desc']
);
```

## Working with Relations

The repository pattern can be combined with Laravel's Eloquent relationships:

```php
// Get all posts for a user
$user = $userRepository->find(1, ['*'], ['posts']);
$posts = $user->posts;

// Filter users by their relation data
$userWithManyPosts = $userRepository->findWhere([
    ['posts_count', '>', 5]
]);

// Using nested relations
$userWithData = $userRepository->find(1, ['*'], ['posts.comments', 'profile']);
```

## Working with Eloquent Scopes

This package supports the use of Eloquent scopes to simplify your queries. Scopes are an excellent way to reuse query logic across different parts of your application.

### Defining Scopes in Your Models

First, define the scopes in your Eloquent model following Laravel conventions:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    /**
     * Scope for users with a specific role
     */
    public function scopeWithRole($query, $role)
    {
        return $query->where('role', $role);
    }
    
    /**
     * Scope for recently registered users
     */
    public function scopeRecentlyRegistered($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
```

### Using Scopes in Repositories

Once the scopes are defined in your model, you can use them in your repositories in various ways:

#### 1. Simple Scopes

```php
// Get all active users
$activeUsers = $userRepository->all(
    ['*'],
    [], // No relations
    [], // No custom ordering
    ['active'] // Apply the 'active' scope
);

// Paginate active users
$paginatedActiveUsers = $userRepository->paginate(
    15, // Records per page
    ['*'], // Columns
    [], // No relations
    [], // No ordering
    [], // No additional conditions
    ['active'] // Apply the 'active' scope
);
```

#### 2. Scopes with Parameters

```php
// Get admin users
$admins = $userRepository->all(
    ['*'], 
    [],
    [],
    [['withRole', 'admin']] // Scope with parameters: ['scope_name', ...parameters]
);

// Get users registered in the last 7 days
$newUsers = $userRepository->findWhere(
    [], // No additional conditions
    ['*'],
    [],
    ['created_at' => 'desc'], // Order by creation date
    [['recentlyRegistered', 7]] // Pass '7' as parameter to scope
);
```

#### 3. Combining Multiple Scopes

```php
// Get recent active admin users
$recentActiveAdmins = $userRepository->paginate(
    10,
    ['*'],
    ['profile'], // Load 'profile' relation
    ['name' => 'asc'],
    [],
    [
        'active', // Scope without parameters
        ['withRole', 'admin'], // Scope with one parameter
        ['recentlyRegistered', 14] // Scope with one parameter
    ]
);
```

#### 4. Using Scopes as Closures

You can also use closures to apply dynamic conditions:

```php
// Search users with custom logic
$filteredUsers = $userRepository->all(
    ['*'],
    ['posts'],
    ['id' => 'desc'],
    [
        // Scope as closure
        function ($query) use ($request) {
            if ($request->has('search')) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            }
            
            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }
        }
    ]
);
```

### Using Scopes in Update and Delete Methods

You can also apply scopes to bulk update and delete methods:

```php
// Update all inactive users
$userRepository->updateWhere(
    ['status' => 'inactive'],
    ['needs_verification' => true],
    [['recentlyRegistered', 180]] // Only for users registered in the last 6 months
);

// Delete unverified guest users
$deleted = $userRepository->deleteWhere(
    ['is_verified' => false],
    [['withRole', 'guest']]
);
```

### Combining Scopes and Custom Conditions

Scopes integrate perfectly with custom conditions:

```php
// Find active users who registered in the last 30 days
// and have a specific role
$users = $userRepository->findWhere(
    [
        ['registration_completed', true], // Custom condition
        ['last_login_at', '>=', now()->subDays(7)] // Another custom condition
    ],
    ['id', 'name', 'email', 'last_login_at'],
    ['profile'], // Load profile relation
    ['created_at' => 'desc'], // Order by creation date (descending)
    [
        'active', // Apply 'active' scope
        ['withRole', 'customer'], // Apply 'withRole' scope with parameter
        ['recentlyRegistered', 30] // Apply 'recentlyRegistered' scope with parameter
    ]
);
```

### Practical Use Cases

#### Example in a Controller

```php
class UserController extends Controller
{
    protected $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function index(Request $request)
    {
        // Prepare dynamic scopes based on request parameters
        $scopes = [];
        
        if ($request->filter === 'active') {
            $scopes[] = 'active';
        }
        
        if ($request->role) {
            $scopes[] = ['withRole', $request->role];
        }
        
        if ($request->recent_days) {
            $scopes[] = ['recentlyRegistered', (int) $request->recent_days];
        }
        
        // Add an anonymous scope for search
        if ($request->search) {
            $scopes[] = function($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            };
        }
        
        // Paginate with applied scopes
        $users = $this->userRepository->paginate(
            $request->per_page ?? 15,
            ['*'],
            ['profile', 'posts'],
            [$request->sort_by ?? 'created_at' => $request->sort_direction ?? 'desc'],
            [], // No additional WHERE conditions
            $scopes
        );
        
        return view('users.index', compact('users'));
    }
}
```

## Recommendations for Using Scopes

* **Reuse**: Create scopes for frequently used queries to keep your code DRY.
* **Descriptive Names**: Use clear names for your scopes that indicate what they do.
* **Scopes vs. Conditions**: For simple logic, use direct conditions. For complex or reusable logic, use scopes.
* **Testing**: Scopes facilitate unit testing of your query logic.

## Complex Queries Example

```php
class UserController extends Controller
{
    protected $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function getActiveSubscribers()
    {
        return $this->userRepository->findWhere(
            [
                'status' => 'active',
                'is_subscriber' => true,
                ['subscription_ends_at', '>', now()]
            ],
            ['id', 'name', 'email', 'subscription_ends_at'],
            ['profile', 'subscriptions'],
            ['subscription_ends_at' => 'asc']
        );
    }
    
    public function getUsersReport()
    {
        $activeUsers = $userRepository->findWhere(['status' => 'active']);
        $inactiveUsers = $userRepository->findWhere(['status' => 'inactive']);
        $pendingUsers = $userRepository->findWhere(['status' => 'pending']);
        
        return view('admin.users.report', compact('activeUsers', 'inactiveUsers', 'pendingUsers'));
    }
    
    public function bulkUpdateSubscriptions()
    {
        // Extend all active subscriptions by 30 days
        $this->userRepository->updateWhere(
            [
                'subscription_status' => 'active',
                ['subscription_ends_at', '<', now()->addDays(5)]
            ],
            [
                'subscription_ends_at' => now()->addDays(30)
            ]
        );
        
        return redirect()->back()->with('success', 'Subscriptions extended successfully');
    }
}
```

## Example Usage in a Controller

```php
use App\Repositories\Contracts\UserRepositoryInterface;

class UserController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index()
    {
        // Example: get all users
        $users = $this->userRepository->all();
        
        // Example: get paginated users
        $paginatedUsers = $this->userRepository->paginate(15);
        
        return view('users.index', compact('users', 'paginatedUsers'));
    }
    
    public function show($id)
    {
        // Find user by ID
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return abort(404);
        }
        
        return view('users.show', compact('user'));
    }
    
    public function store(Request $request)
    {
        // Create a new user
        $user = $this->userRepository->create($request->validated());
        
        return redirect()->route('users.show', $user->id);
    }
}
```

## Working with Nested Repositories

You can organize your repositories into subfolders:

```sh
php artisan make:repository Admin/UserRepository User
```

This will create:
- `app/Repositories/Admin/UserRepository.php`
- `app/Repositories/Contracts/Admin/UserRepositoryInterface.php`

And you would use it like:

```php
use App\Repositories\Contracts\Admin\UserRepositoryInterface;

class AdminUserController extends Controller
{
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    // ...
}
```

## Repository Traits for Modular Architecture

Starting with version 1.5.0, the package includes specialized traits that facilitate creating more modular, maintainable, and clean repositories. These traits divide functionality into specific components that can be combined as needed.

### Available Traits

1. **QueryableTrait**: For handling queries (where, select, etc.)
2. **RelationshipTrait**: For optimized relationship management
3. **ScopableTrait**: For working with Eloquent scopes
4. **CrudOperationsTrait**: For basic CRUD operations
5. **PaginationTrait**: For different pagination methods
6. **TransactionTrait**: For database transaction handling

### Usage Example

```php
use Juankno\Repository\Traits\CrudOperationsTrait;
use Juankno\Repository\Traits\QueryableTrait;
use Juankno\Repository\Traits\RelationshipTrait;

class ProductRepository implements ProductRepositoryInterface
{
    use CrudOperationsTrait, QueryableTrait, RelationshipTrait;
    
    protected $model;
    
    public function __construct(Product $model)
    {
        $this->model = $model;
    }
    
    // Additional custom methods...
}
```

For detailed information about each trait and its specific methods, see [our traits documentation](README.traits.md).

### Generating Repositories with Traits

The `make:repository` command now automatically generates repositories using these traits when not using the `--abstract` option. This makes the generated code cleaner and more maintainable:

```php
// Automatically generated repository
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
        
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
```

### Benefits of Using Traits

1. **Cleaner Code**: Each trait has a single, clear responsibility
2. **Improved Maintainability**: Easier to update logic in one place
3. **Flexibility**: Use only the traits needed for each repository
4. **Reduced Code Duplication**: Common logic is centralized
5. **Better Testability**: Each trait can be tested independently

## Enhanced Configuration

The package now includes expanded configuration options to customize the behavior of repositories and traits:

```php
// config/repository.php

return [
    'cache' => [
        'enabled' => true,
        'ttl' => 60,
        'key_prefix' => 'laravel_repository_',
        'skip_in_development' => true,
    ],
    'relations' => [
        'auto_load_count' => true,
        'max_eager_relations' => 5,
        'allow_nested_relations' => true,
        'debug_relations' => false,
    ],
    'query' => [
        'use_direct_update' => true,
        'use_direct_delete' => true,
        'chunk_size' => 1000,
        'optimize_selects' => true,
    ],
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
    ],
    // ...other configurations
];
```

To publish the configuration file:

```sh
php artisan vendor:publish --tag=repository-config
```

## Contributions

Contributions are welcome!  
Feel free to submit a **pull request** or open an **issue** to discuss improvements.

## License

This project is open-source and available under the **MIT License**.
