# Laravel Repository

🌎 Lea esto en [Inglés](README.md).

Este paquete simplifica el trabajo con el **Patrón de Repositorio** en Laravel al generar automáticamente archivos de repositorio, contratos y enlaces.

## Instalación

Instale el paquete usando Composer:

```sh
composer require juankno/laravel-repository
```

## Configuración

Si Laravel no detecta automáticamente el paquete, registre manualmente el `RepositoryServiceProvider` en `config/app.php`:

```php
'providers' => [
    Juankno\Repository\Providers\RepositoryServiceProvider::class,
],
```

## Uso

### Creando un Repositorio

Para generar un nuevo repositorio, ejecute el siguiente comando Artisan:

```sh
php artisan make:repository RepositoryName
```

## Comandos Disponibles

### `make:repository`

Este comando genera un repositorio junto con su contrato e implementación.

#### **Uso:**
```sh
php artisan make:repository {name} {model?}
```

#### **Argumentos:**
- `name` _(requerido)_: El nombre del repositorio.
- `model` _(opcional)_: El modelo Eloquent asociado.

#### **Ejemplo:**
```sh
php artisan make:repository UserRepository User
```

Este comando generará:
- `app/Repositories/UserRepository.php`
- `app/Repositories/Contracts/UserRepositoryInterface.php`

Si no se especifica un modelo, el comando asume que el nombre del modelo coincide con el nombre del repositorio, menos el sufijo `Repository`.

## Ejemplo de Uso

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

## Contribuciones

¡Las contribuciones son bienvenidas!  
No dude en enviar una **pull request** o abrir un **issue** para discutir mejoras.

## Licencia

Este proyecto es de código abierto y está disponible bajo la **Licencia MIT**.
