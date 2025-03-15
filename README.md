# Laravel Repository

This package provides functionalities to work with the Repository pattern in Laravel.

## Installation

You can install the package using Composer:

```bash
composer require juankno/laravel-repository
```

## Configuration

After installing the package, add the `RepositoryServiceProvider` to the `providers` array in `config/app.php`:

```php
'providers' => [
    // ...existing code...
    Juankno\Repository\Providers\RepositoryServiceProvider::class,
    // ...existing code...
],
```

## Usage

### Create a Repository

To create a new repository, use the following Artisan command:

```bash
php artisan make:repository RepositoryName
```

## Commands

### make:repository

This command creates a repository with its contract and implementation.

**Usage:**
```sh
php artisan make:repository {name} {model?}
```

**Arguments:**
- `name`: The name of the repository.
- `model` (optional): The name of the associated Eloquent model.

**Example:**
```sh
php artisan make:repository UserRepository User
```

This command will create the following files:
- `app/Repositories/UserRepository.php`
- `app/Repositories/Contracts/UserRepositoryInterface.php`

If the model is not provided, it is assumed that the model name is the same as the repository name without the `Repository` suffix.

### Usage Example

```php
use App\Repositories\RepositoryName;

class ExampleController extends Controller
{
    protected $repository;

    public function __construct(RepositoryName $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        $data = $this->repository->all();
        return view('example.index', compact('data'));
    }
}
```

## Contributions

Contributions are welcome. Please submit a pull request or open an issue to discuss the changes you would like to make.

## License

This project is licensed under the MIT License.
