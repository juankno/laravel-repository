# Laravel Repository

🌎 Lea esto en [Inglés](README.md).

Este paquete simplifica el trabajo con el **Patrón de Repositorio** en Laravel al generar automáticamente archivos de repositorio, contratos y enlaces.

## 🆕 **Nuevo en v1.7.0** - Simple por Defecto

El comportamiento del comando ha sido mejorado para seguir el **principio de menor sorpresa**:

- **Por defecto**: Crea un repositorio básico con métodos CRUD esenciales (find, getAll, create, update, delete)
- **Avanzado**: Usa la bandera `--full` para obtener todas las características avanzadas (filtrado, scoping, operaciones en lote, etc.)

Esto hace que el paquete sea más amigable para principiantes mientras mantiene todo el poder para usuarios avanzados.

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

Para generar un repositorio básico (recomendado), ejecute el siguiente comando Artisan:

```sh
php artisan make:repository UserRepository
```

Esto crea un repositorio simple con operaciones CRUD básicas (find, getAll, create, update, delete).

Si desea asociarlo con un modelo específico:

```sh
php artisan make:repository UserRepository User
```

Para un repositorio con **todos los métodos y características avanzadas**, use la opción `--full`:

```sh
php artisan make:repository UserRepository User --full
```

### Generando un Repositorio Base

Puede generar una clase abstracta `BaseRepository` junto con su interfaz para evitar la duplicación de código:

```sh
php artisan make:repository UserRepository User --abstract
```

Esto creará un `BaseRepository` y `BaseRepositoryInterface` en su aplicación, que otros repositorios pueden extender.

### Creando un Repositorio con Todas las Características

Si necesita todas las características y métodos avanzados, use la opción `--full`:

```sh
php artisan make:repository UserRepository --full
```

Esto crea un repositorio con todos los métodos disponibles incluyendo filtrado avanzado, scoping, operaciones en lote y más.

## Comandos Disponibles

### `make:repository`

Este comando genera un repositorio junto con su contrato e implementación.

#### **Uso:**
```sh
php artisan make:repository {name} {model?} {--force} {--abstract} {--full} {--no-traits}
```

#### **Argumentos:**
- `name` _(requerido)_: El nombre del repositorio.
- `model` _(opcional)_: El modelo Eloquent asociado.

#### **Opciones:**
- `--force`: Sobrescribe archivos existentes.
- `--abstract`: Genera también clases base abstractas.
- `--full`: Crea un repositorio con todos los métodos y características avanzadas.
- `--no-traits`: Crea un repositorio con toda la implementación en la clase sin utilizar traits.

#### **Ejemplos:**

```sh
# Crear un repositorio básico (recomendado para la mayoría de casos)
php artisan make:repository UserRepository User

# Crear un repositorio con todos los métodos avanzados
php artisan make:repository UserRepository User --full

# Crear un repositorio en una subcarpeta
php artisan make:repository Admin/UserRepository User

# Crear un repositorio y generar BaseRepository
php artisan make:repository UserRepository User --abstract

# Forzar sobrescritura de archivos existentes
php artisan make:repository UserRepository User --force

# Crear repositorio sin traits (implementación directa)
php artisan make:repository UserRepository User --no-traits

# Forzar sobrescritura de archivos existentes
php artisan make:repository UserRepository User --force

# Crear un repositorio vacío sin métodos predefinidos
php artisan make:repository UserRepository --empty

# Crear un repositorio con implementación completa sin usar traits
php artisan make:repository UserRepository --no-traits
```

## Métodos Disponibles en el Repositorio

### Métodos del Repositorio Básico (Por Defecto)

Cada repositorio básico incluye estos métodos CRUD esenciales:

- `find(int $id)`: Encontrar un registro por ID.
- `getAll()`: Obtener todos los registros.
- `create(array $data)`: Crear un nuevo registro.
- `update(int $id, array $data)`: Actualizar un registro.
- `delete(int $id)`: Eliminar un registro.

### Métodos del Repositorio Completo (opción --full)

Cuando se usa la opción `--full`, los repositorios incluyen todos estos métodos avanzados:

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

## Trabajando con Scopes de Eloquent

Este paquete soporta el uso de scopes de Eloquent para simplificar tus consultas. Los scopes son una forma excelente de reutilizar lógica de consulta entre diferentes partes de tu aplicación.

### Definiendo Scopes en tus Modelos

Primero, define los scopes en tu modelo Eloquent siguiendo las convenciones de Laravel:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /**
     * Scope para usuarios activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    /**
     * Scope para usuarios con un rol específico
     */
    public function scopeWithRole($query, $role)
    {
        return $query->where('role', $role);
    }
    
    /**
     * Scope para usuarios registrados recientemente
     */
    public function scopeRecentlyRegistered($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
```

### Usando Scopes en los Repositorios

Una vez que los scopes están definidos en tu modelo, puedes usarlos en tus repositorios de varias maneras:

#### 1. Scopes Simples

```php
// Obtener todos los usuarios activos
$activeUsers = $userRepository->all(
    ['*'],
    [], // Sin relaciones
    [], // Sin ordenamiento personalizado
    ['active'] // Aplicar el scope 'active'
);

// Paginar usuarios activos
$paginatedActiveUsers = $userRepository->paginate(
    15, // Registros por página
    ['*'], // Columnas
    [], // Sin relaciones
    [], // Sin ordenamiento
    [], // Sin condiciones adicionales
    ['active'] // Aplicar el scope 'active'
);
```

#### 2. Scopes con Parámetros

```php
// Obtener administradores
$admins = $userRepository->all(
    ['*'], 
    [],
    [],
    [['withRole', 'admin']] // Scope con parámetros: ['nombre_del_scope', ...parámetros]
);

// Obtener usuarios registrados en los últimos 7 días
$newUsers = $userRepository->findWhere(
    [], // Sin condiciones adicionales
    ['*'],
    [],
    ['created_at' => 'desc'], // Ordenar por fecha de creación
    [['recentlyRegistered', 7]] // Pasar '7' como parámetro al scope
);
```

#### 3. Combinando Múltiples Scopes

```php
// Obtener administradores activos recientes
$recentActiveAdmins = $userRepository->paginate(
    10,
    ['*'],
    ['profile'], // Cargar relación 'profile'
    ['name' => 'asc'],
    [],
    [
        'active', // Scope sin parámetros
        ['withRole', 'admin'], // Scope con un parámetro
        ['recentlyRegistered', 14] // Scope con un parámetro
    ]
);
```

#### 4. Usando Scopes como Closures

También puedes usar closures para aplicar condiciones dinámicas:

```php
// Buscar usuarios con lógica personalizada
$filteredUsers = $userRepository->all(
    ['*'],
    ['posts'],
    ['id' => 'desc'],
    [
        // Scope como closure
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

### Utilizando Scopes en Métodos de Actualización y Eliminación

También puedes aplicar scopes a los métodos de actualización y eliminación masiva:

```php
// Actualizar todos los usuarios inactivos
$userRepository->updateWhere(
    ['status' => 'inactive'],
    ['needs_verification' => true],
    [['recentlyRegistered', 180]] // Solo para usuarios registrados en los últimos 6 meses
);

// Eliminar usuarios con rol de invitado no verificados
$deleted = $userRepository->deleteWhere(
    ['is_verified' => false],
    [['withRole', 'guest']]
);
```

### Combinando Scopes y Condiciones Personalizadas

Los scopes se integran perfectamente con las condiciones personalizadas:

```php
// Encuentra usuarios activos que se registraron en los últimos 30 días
// y tienen un rol específico
$users = $userRepository->findWhere(
    [
        ['registration_completed', true], // Condición personalizada
        ['last_login_at', '>=', now()->subDays(7)] // Otra condición personalizada
    ],
    ['id', 'name', 'email', 'last_login_at'],
    ['profile'], // Cargar relación profile
    ['created_at' => 'desc'], // Ordenar por fecha de creación (descendente)
    [
        'active', // Aplicar scope 'active'
        ['withRole', 'customer'], // Aplicar scope 'withRole' con parámetro
        ['recentlyRegistered', 30] // Aplicar scope 'recentlyRegistered' con parámetro
    ]
);
```

### Casos de Uso Prácticos

#### Ejemplo en un Controlador

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
        // Preparación de scopes dinámicos según parámetros de la petición
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
        
        // Agregar un scope anónimo para búsqueda
        if ($request->search) {
            $scopes[] = function($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            };
        }
        
        // Paginación con los scopes aplicados
        $users = $this->userRepository->paginate(
            $request->per_page ?? 15,
            ['*'],
            ['profile', 'posts'],
            [$request->sort_by ?? 'created_at' => $request->sort_direction ?? 'desc'],
            [], // Sin condiciones WHERE adicionales
            $scopes
        );
        
        return view('users.index', compact('users'));
    }
}
```

## Recomendaciones para usar Scopes

* **Reutilización**: Crea scopes para consultas frecuentes para mantener tu código DRY.
* **Nombres Descriptivos**: Usa nombres claros para tus scopes que indiquen lo que hacen.
* **Scopes vs. Condiciones**: Para lógica simple, usa condiciones directas. Para lógica compleja o reutilizable, usa scopes.
* **Testing**: Los scopes facilitan las pruebas unitarias de tu lógica de consulta.

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

## Traits para Repositorios Modulares

A partir de la versión actual, el paquete incluye un conjunto de traits especializados que facilitan la creación de repositorios más modulares, mantenibles y limpios. Estos traits dividen la funcionalidad en componentes específicos que pueden combinarse según sea necesario.

### Traits Disponibles

1. **QueryableTrait**: Para manejar consultas (where, select, etc.)
2. **RelationshipTrait**: Para manejar relaciones de manera optimizada
3. **ScopableTrait**: Para trabajar con scopes de Eloquent
4. **CrudOperationsTrait**: Para operaciones básicas CRUD
5. **PaginationTrait**: Para diferentes tipos de paginación
6. **TransactionTrait**: Para manejo de transacciones

### Ejemplo de Uso

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
    
    // Métodos personalizados adicionales...
}
```

Para más detalles sobre cómo usar cada trait y sus métodos específicos, consulte [nuestra documentación de traits](README.traits.md).

### Generando Repositorios con Traits

El comando `make:repository` ahora genera automáticamente repositorios utilizando estos traits cuando no se usa la opción `--abstract`. Esto hace que el código sea más limpio y mantenible:

```php
// Repositorio generado automáticamente
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

### Ventajas de Usar Traits

1. **Código más limpio**: Cada trait tiene una responsabilidad única y clara
2. **Mayor mantenibilidad**: Es más fácil actualizar la lógica en un solo lugar
3. **Flexibilidad**: Use solo los traits que necesite para cada repositorio
4. **Reducción de código duplicado**: La lógica común está centralizada
5. **Mejor testabilidad**: Cada trait puede probarse de forma independiente

## Configuración Mejorada

El paquete ahora incluye opciones de configuración ampliadas para personalizar el comportamiento de los repositorios y traits:

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
    // ...otras configuraciones
];
```

Para publicar el archivo de configuración:

```sh
php artisan vendor:publish --tag=repository-config
```

## 🔒 Seguridad

### Protecciones Implementadas (v1.7.1)

Este paquete incluye múltiples capas de seguridad para proteger tu aplicación:

#### 1. Prevención de SQL Injection
- ✅ Validación automática de nombres de columnas
- ✅ Validación de direcciones ORDER BY contra whitelist
- ✅ Lanza excepciones cuando se detectan nombres de columna inválidos

```php
// ❌ Esto lanzará InvalidArgumentException
$repository->findWhere(['id; DROP TABLE--' => 1]);

// ✅ Esto funciona correctamente
$repository->findWhere(['id' => 1]);
```

#### 2. Protección contra DoS
- ✅ Límite máximo de resultados configurable (default: 10,000)
- ✅ Previene consultas que agoten la memoria del servidor

```php
'security' => [
    'max_results_limit' => 10000, // 0 = sin límite
]
```

#### 3. Sanitización de Logs
- ✅ Los valores sensibles en logs SQL son reemplazados con `[REDACTED]`
- ✅ Previene exposición de datos sensibles en archivos de log

#### 4. Validación de Rutas
- ✅ Protección contra path traversal en generación de archivos
- ✅ Validación estricta de nombres de repositorios

### Configuración de Seguridad

```php
// config/repository.php
'security' => [
    'validate_column_names' => true,
    'max_results_limit' => 10000,
    'allowed_order_directions' => ['asc', 'desc', 'ASC', 'DESC'],
    'sanitize_logs' => true,
    'strict_column_validation' => false,
],
```

### Responsabilidades del Desarrollador

El paquete implementa protecciones, pero tú debes:

1. **Validar entrada de usuario**
```php
$validated = $request->validate([
    'name' => 'required|string|max:255',
]);
$repository->create($validated);
```

2. **Definir $fillable o $guarded en modelos**
```php
protected $fillable = ['name', 'email'];
```

3. **Implementar autorización**
```php
if (!auth()->user()->can('update', $model)) {
    abort(403);
}
```

4. **Usar paginación en lugar de all()**
```php
$records = $repository->paginate(50);
```

📖 **Más información**: Consulta [SECURITY.md](SECURITY.md) para detalles completos.

## Contribuciones

¡Las contribuciones son bienvenidas!  
No dude en enviar una **pull request** o abrir un **issue** para discutir mejoras.

## Licencia

Este proyecto es de código abierto y está disponible bajo la **Licencia MIT**.
