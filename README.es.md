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

- `all(array $columns = ['*'], array $relations = [], array $orderBy = [])`: Obtener todos los registros.
- `find(int $id, array $columns = ['*'], array $relations = [], array $appends = [])`: Encontrar un registro por ID.
- `findBy(string $field, $value, array $columns = ['*'], array $relations = [])`: Encontrar un registro por un campo específico.
- `findWhere(array $conditions, array $columns = ['*'], array $relations = [], array $orderBy = [])`: Encontrar registros que coincidan con condiciones.
- `paginate(int $perPage = 15, array $columns = ['*'], array $relations = [], array $orderBy = [], array $conditions = [])`: Paginar registros.
- `create(array $data)`: Crear un nuevo registro.
- `update(int $id, array $data)`: Actualizar un registro.
- `delete(int $id)`: Eliminar un registro.
- `first(array $conditions = [], array $columns = ['*'], array $relations = [], array $orderBy = [])`: Obtener el primer registro que coincida con las condiciones.
- `createMany(array $data)`: Crear múltiples registros en una sola operación.
- `updateWhere(array $conditions, array $data)`: Actualizar múltiples registros según las condiciones.
- `deleteWhere(array $conditions)`: Eliminar múltiples registros según las condiciones.

## Ejemplos Detallados de Métodos

### Recuperando Todos los Registros

```php
// Obtener todos los usuarios
$users = $userRepository->all();

// Obtener columnas específicas
$userNames = $userRepository->all(['id', 'name', 'email']);

// Obtener registros con relaciones
$usersWithPosts = $userRepository->all(['*'], ['posts']);

// Obtener registros con ordenamiento personalizado
$usersByNewest = $userRepository->all(['*'], [], ['created_at' => 'desc']);

// Obtener registros con múltiples relaciones y ordenamiento
$users = $userRepository->all(
    ['*'],
    ['posts', 'profile', 'roles'],
    ['name' => 'asc']
);
```

### Encontrando Registros por ID

```php
// Encontrar usuario por ID
$user = $userRepository->find(1);

// Encontrar usuario con columnas específicas
$user = $userRepository->find(1, ['id', 'name', 'email']);

// Encontrar usuario y cargar relaciones
$userWithPosts = $userRepository->find(1, ['*'], ['posts']);

// Encontrar usuario con atributos añadidos
$userWithFullName = $userRepository->find(1, ['*'], [], ['full_name']);

// Encontrar usuario con relaciones y atributos añadidos
$user = $userRepository->find(
    1,
    ['*'],
    ['posts', 'comments'],
    ['full_name', 'post_count']
);
```

### Encontrando Registros por un Campo Específico

```php
// Encontrar usuario por email
$user = $userRepository->findBy('email', 'john@example.com');

// Encontrar usuario por nombre de usuario con columnas específicas
$user = $userRepository->findBy('username', 'johndoe', ['id', 'username', 'email']);

// Encontrar usuario con relaciones
$user = $userRepository->findBy('email', 'john@example.com', ['*'], ['posts', 'profile']);
```

### Encontrando Registros con Condiciones

```php
// Encontrar usuarios activos
$activeUsers = $userRepository->findWhere(['status' => 'active']);

// Encontrar usuarios con rol específico
$adminUsers = $userRepository->findWhere(['role' => 'admin'], ['id', 'name', 'email']);

// Usar operadores en condiciones
$recentUsers = $userRepository->findWhere([
    ['created_at', '>=', now()->subDays(7)]
]);

// Encontrar usuarios con múltiples condiciones y cargar relaciones
$users = $userRepository->findWhere(
    [
        'status' => 'active',
        ['age', '>', 18]
    ],
    ['*'],
    ['posts', 'profile']
);

// Encontrar usuarios con IDs específicos (whereIn)
$specificUsers = $userRepository->findWhere(['id' => [1, 2, 3]]);

// Encontrar con ordenamiento personalizado
$users = $userRepository->findWhere(
    ['status' => 'active'],
    ['*'],
    ['profile'],
    ['name' => 'asc']
);
```

### Paginando Registros

```php
// Paginar usuarios (15 por página por defecto)
$paginatedUsers = $userRepository->paginate();

// Paginación personalizada
$paginatedUsers = $userRepository->paginate(25);

// Paginar con columnas específicas
$paginatedUsers = $userRepository->paginate(10, ['id', 'name', 'email']);

// Paginar y cargar relaciones
$paginatedUsers = $userRepository->paginate(20, ['*'], ['posts']);

// Paginar con condiciones
$paginatedActiveUsers = $userRepository->paginate(
    15,
    ['*'],
    [],
    ['created_at' => 'desc'],
    ['status' => 'active']
);

// Mostrar resultados paginados en una vista
return view('users.index', compact('paginatedUsers'));
```

### Creando Registros

```php
// Crear un nuevo usuario
$userData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password')
];
$user = $userRepository->create($userData);

// Crear y usar inmediatamente
$post = $postRepository->create([
    'title' => 'Nuevo Post',
    'content' => 'Contenido del post',
    'user_id' => $user->id
]);
```

### Creando Múltiples Registros

```php
// Crear múltiples usuarios a la vez
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

// Acceder a los modelos creados
foreach ($users as $user) {
    echo $user->id . ': ' . $user->name . "\n";
}
```

### Actualizando Registros

```php
// Actualizar un usuario
$updatedUser = $userRepository->update(1, [
    'name' => 'Nombre Actualizado',
    'email' => 'actualizado@example.com'
]);

// Comprobar si la actualización fue exitosa
if ($updatedUser) {
    // Actualización exitosa, $updatedUser contiene la instancia actualizada del modelo
} else {
    // La actualización falló o el usuario no fue encontrado
}
```

### Actualizando Registros en Masa

```php
// Actualizar todos los usuarios activos para que tengan un estado verificado
$updated = $userRepository->updateWhere(
    ['status' => 'active'],
    ['is_verified' => true]
);

// Actualizar usuarios con rol específico y creados antes de una fecha determinada
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

### Eliminando Registros

```php
// Eliminar un usuario
$deleted = $userRepository->delete(1);

// Comprobar si la eliminación fue exitosa
if ($deleted) {
    // El usuario fue eliminado exitosamente
} else {
    // La eliminación falló o el usuario no fue encontrado
}
```

### Eliminando Múltiples Registros

```php
// Eliminar usuarios inactivos
$deleted = $userRepository->deleteWhere(['status' => 'inactive']);

// Eliminar usuarios que no han iniciado sesión durante un año
$deleted = $userRepository->deleteWhere([
    ['last_login_at', '<', now()->subYear()]
]);

// Eliminar usuarios con roles específicos
$deleted = $userRepository->deleteWhere([
    'role' => ['guest', 'inactive', 'blocked']
]);

// El valor de retorno es el número de registros eliminados
echo "Se eliminaron {$deleted} registros";
```

### Obteniendo el Primer Registro que Coincida

```php
// Obtener el primer usuario administrador activo
$admin = $userRepository->first(['role' => 'admin', 'status' => 'active']);

// Obtener el primero con columnas específicas
$user = $userRepository->first(
    ['status' => 'active'],
    ['id', 'name', 'email']
);

// Obtener el primero con relaciones
$user = $userRepository->first(
    ['role' => 'editor'],
    ['*'],
    ['posts', 'profile']
);

// Obtener el primero con condiciones complejas y ordenamiento personalizado
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

## Trabajando con Relaciones

El patrón repositorio se puede combinar con las relaciones de Eloquent:

```php
// Obtener todas las publicaciones de un usuario
$user = $userRepository->find(1, ['*'], ['posts']);
$posts = $user->posts;

// Filtrar usuarios por datos de relación
$userWithManyPosts = $userRepository->findWhere([
    ['posts_count', '>', 5]
]);

// Usar relaciones anidadas
$userWithData = $userRepository->find(1, ['*'], ['posts.comments', 'profile']);
```

## Ejemplo de Consultas Complejas

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
        $activeUsers = $this->userRepository->findWhere(['status' => 'active']);
        $inactiveUsers = $this->userRepository->findWhere(['status' => 'inactive']);
        $pendingUsers = $this->userRepository->findWhere(['status' => 'pending']);
        
        return view('admin.users.report', compact('activeUsers', 'inactiveUsers', 'pendingUsers'));
    }
    
    public function bulkUpdateSubscriptions()
    {
        // Extender todas las suscripciones activas por 30 días
        $this->userRepository->updateWhere(
            [
                'subscription_status' => 'active',
                ['subscription_ends_at', '<', now()->addDays(5)]
            ],
            [
                'subscription_ends_at' => now()->addDays(30)
            ]
        );
        
        return redirect()->back()->with('success', 'Suscripciones extendidas exitosamente');
    }
}
```

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
