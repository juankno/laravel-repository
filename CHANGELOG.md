# CHANGELOG

Todos los cambios notables a este proyecto serán documentados en este archivo.

## [1.3.0] - 2025-04-20

### Añadido
- Soporte para scopes de Eloquent en todos los métodos de consulta (all, find, findBy, findWhere, paginate, etc.)
- Nuevo método `applyScopes()` en BaseRepository para aplicar scopes de forma flexible
- Documentación sobre cómo usar scopes con ejemplos prácticos

## [1.2.0] - 2025-04-18

### Añadido
- Soporte para cargar relaciones de Eloquent en todos los métodos de consulta
- Nuevos métodos para operaciones masivas: `createMany()`, `updateWhere()` y `deleteWhere()`
- Soporte para ordenamiento personalizado mediante el parámetro `orderBy`
- Soporte para atributos calculados con el parámetro `appends` en el método `find()`
- Tipado estricto en todos los métodos con tipos de retorno adecuados
- Manejo mejorado de condiciones WHERE, incluyendo operadores personalizados y condiciones WHERE IN
- Documentación exhaustiva con ejemplos detallados para cada método

### Mejorado
- Mejor manejo de errores en métodos como `update()` y `delete()`
- Implementaciones más limpias usando características de PHP 8 como el operador nullsafe
- La documentación ahora incluye ejemplos prácticos en inglés y español

## [1.1.0] - 2024-03-15

### Añadido
- Nueva opción `--empty` para el comando `make:repository` que permite crear repositorios vacíos sin métodos predefinidos.

### Actualizado
- Documentación en README.md y README.es.md para incluir información sobre la nueva opción `--empty`.

## [1.0.0] - Versión inicial

### Añadido
- Implementación inicial del patrón repositorio para Laravel.
- Comando `make:repository` para generar automáticamente repositorios, contratos y enlaces.
- Soporte para las opciones `--force` y `--abstract`.
- Documentación en inglés y español.