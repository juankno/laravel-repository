# Laravel Repository

🌎 Read this in [Spanish](README.es.md).

This package simplifies working with the **Repository Pattern** in Laravel by automatically generating repository files, contracts, and bindings.

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

To generate a new repository, run the following Artisan command:

```sh
php artisan make:repository RepositoryName
```

If you want to associate it with a specific model:

```sh
php artisan make:repository UserRepository User
```

### Generating a Base Repository

You can generate an abstract `BaseRepository` class along with its interface to avoid code duplication:

```sh
php artisan make:repository UserRepository User --abstract
```

This will create a `BaseRepository` and `BaseRepositoryInterface` in your application, which other repositories can extend.

## Available Commands

### `make:repository`

This command generates a repository along with its contract and implementation.

#### **Usage:**
```sh
php artisan make:repository {name} {model?} {--force} {--abstract}
```

#### **Arguments:**
- `name` _(required)_: The name of the repository.
- `model` _(optional)_: The associated Eloquent model.

#### **Options:**
- `--force`: Overwrite existing files if they already exist.
- `--abstract`: Generate a BaseRepository and BaseRepositoryInterface.

#### **Examples:**

```sh
# Create a basic repository
php artisan make:repository UserRepository User

# Create a repository in a subfolder
php artisan make:repository Admin/UserRepository User

# Create a repository and generate BaseRepository
php artisan make:repository UserRepository User --abstract

# Force overwrite of existing files
php artisan make:repository UserRepository User --force
```

## Available Repository Methods

Each generated repository includes the following methods:

- `all(array $columns = ['*'])`: Get all records.
- `find(int $id, array $columns = ['*'])`: Find a record by ID.
- `findBy(string $field, $value, array $columns = ['*'])`: Find a record by a specific field.
- `findWhere(array $conditions, array $columns = ['*'])`: Find records matching conditions.
- `paginate(int $perPage = 15, array $columns = ['*'])`: Paginate records.
- `create(array $data)`: Create a new record.
- `update(int $id, array $data)`: Update a record.
- `delete(int $id)`: Delete a record.
- `first(array $conditions = [], array $columns = ['*'])`: Get the first record matching conditions.

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

## Contributions

Contributions are welcome!  
Feel free to submit a **pull request** or open an **issue** to discuss improvements.

## License

This project is open-source and available under the **MIT License**.
