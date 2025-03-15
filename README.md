# Laravel Repository

Este paquete proporciona funcionalidades para trabajar con el patrón Repository en Laravel.

## Instalación

Puedes instalar el paquete usando Composer:

```bash
composer require juankno/laravel-repository
```

## Configuración

Después de instalar el paquete, agrega el `RepositoryServiceProvider` al arreglo de `providers` en `config/app.php`:

```php
'providers' => [
    // ...existing code...
    Juankno\Repository\Providers\RepositoryServiceProvider::class,
    // ...existing code...
],
```

## Uso

### Crear un Repositorio

Para crear un nuevo repositorio, usa el siguiente comando de Artisan:

```bash
php artisan make:repository NombreDelRepositorio
```

### Ejemplo de Uso

```php
use App\Repositories\NombreDelRepositorio;

class EjemploController extends Controller
{
    protected $repositorio;

    public function __construct(NombreDelRepositorio $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    public function index()
    {
        $datos = $this->repositorio->all();
        return view('ejemplo.index', compact('datos'));
    }
}
```

## Contribuciones

Las contribuciones son bienvenidas. Por favor, envía un pull request o abre un issue para discutir los cambios que te gustaría realizar.

## Licencia

Este proyecto está licenciado bajo la Licencia MIT.
