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

## Available Commands

### `make:repository`

This command generates a repository along with its contract and implementation.

#### **Usage:**
```sh
php artisan make:repository {name} {model?}
```

#### **Arguments:**
- `name` _(required)_: The name of the repository.
- `model` _(optional)_: The associated Eloquent model.

#### **Example:**
```sh
php artisan make:repository UserRepository User
```

This command will generate:
- `app/Repositories/UserRepository.php`
- `app/Repositories/Contracts/UserRepositoryInterface.php`

If no model is specified, the command assumes that the model name matches the repository name, minus the `Repository` suffix.

## Example Usage

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
        $users = $this->userRepository->all();
        return view('users.index', compact('users'));
    }
}
```

## Contributions

Contributions are welcome!  
Feel free to submit a **pull request** or open an **issue** to discuss improvements.

## License

This project is open-source and available under the **MIT License**.
