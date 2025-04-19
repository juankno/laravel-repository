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

## Publicando el Proveedor de Servicios del Repositorio

Si desea personalizar el `RepositoryServiceProvider`, puede publicarlo usando:

```sh
php artisan vendor:publish --tag=repository-provider
```

## Uso

### Creando un Repositorio

Para generar un nuevo repositorio, ejecute el siguiente comando Artisan:

```sh
php artisan make:repository RepositoryName
```

Si desea asociarlo con un modelo específico:

```sh
php artisan make:repository UserRepository User
```

### Generando un Repositorio Base

Puede generar una clase abstracta `BaseRepository` junto con su interfaz para evitar la duplicación de código:

```sh
php artisan make:repository UserRepository User --abstract
```

Esto creará un `BaseRepository` y `BaseRepositoryInterface` en su aplicación, que otros repositorios pueden extender.

### Creando un Repositorio Vacío

Si desea crear un repositorio sin ningún método predefinido, utilice la opción `--empty`:

```sh
php artisan make:repository UserRepository --empty
```

Esto crea una estructura de repositorio e interfaz sin métodos predefinidos, permitiéndole definir sus propios métodos personalizados.

## Comandos Disponibles

### `make:repository`

Este comando genera un repositorio junto con su contrato e implementación.

#### **Uso:**
```sh
php artisan make:repository {name} {model?} {--force} {--abstract} {--empty}
```

#### **Argumentos:**
- `name` _(requerido)_: El nombre del repositorio.
- `model` _(opcional)_: El modelo Eloquent asociado.

#### **Opciones:**
- `--force`: Sobrescribe archivos existentes.
- `--abstract`: Genera también clases base abstractas.
- `--empty`: Crea un repositorio vacío sin métodos predefinidos.

#### **Ejemplos:**

```sh
# Crear un repositorio básico
php artisan make:repository UserRepository User

# Crear un repositorio en una subcarpeta
php artisan make:repository Admin/UserRepository User

# Crear un repositorio y generar BaseRepository
php artisan make:repository UserRepository User --abstract

# Forzar sobrescritura de archivos existentes
php artisan make:repository UserRepository User --force

# Crear un repositorio vacío sin métodos predefinidos
php artisan make:repository UserRepository --empty
```

## Métodos Disponibles en el Repositorio

Cada repositorio generado incluye los siguientes métodos (a menos que se cree con la opción `--empty`):

- `all(array $columns = ['*'])`: Obtener todos los registros.
- `find(int $id, array $columns = ['*'])`: Encontrar un registro por ID.
- `findBy(string $field, $value, array $columns = ['*'])`: Encontrar un registro por un campo específico.
- `findWhere(array $conditions, array $columns = ['*'])`: Encontrar registros que coincidan con condiciones.
- `paginate(int $perPage = 15, array $columns = ['*'])`: Paginar registros.
- `create(array $data)`: Crear un nuevo registro.
- `update(int $id, array $data)`: Actualizar un registro.
- `delete(int $id)`: Eliminar un registro.
- `first(array $conditions = [], array $columns = ['*'])`: Obtener el primer registro que coincida con las condiciones.

## Ejemplo de Uso en un Controlador

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
        // Ejemplo: obtener todos los usuarios
        $users = $this->userRepository->all();
        
        // Ejemplo: obtener usuarios paginados
        $paginatedUsers = $this->userRepository->paginate(15);
        
        return view('users.index', compact('users', 'paginatedUsers'));
    }
    
    public function show($id)
    {
        // Encontrar usuario por ID
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return abort(404);
        }
        
        return view('users.show', compact('user'));
    }
    
    public function store(Request $request)
    {
        // Crear un nuevo usuario
        $user = $this->userRepository->create($request->validated());
        
        return redirect()->route('users.show', $user->id);
    }
}
```

## Trabajando con Repositorios Anidados

Puede organizar sus repositorios en subcarpetas:

```sh
php artisan make:repository Admin/UserRepository User
```

Esto creará:
- `app/Repositories/Admin/UserRepository.php`
- `app/Repositories/Contracts/Admin/UserRepositoryInterface.php`

Y lo usaría así:

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

## Contribuciones

¡Las contribuciones son bienvenidas!  
No dude en enviar una **pull request** o abrir un **issue** para discutir mejoras.

## Licencia

Este proyecto es de código abierto y está disponible bajo la **Licencia MIT**.
