# CHANGELOG

All notable changes to this project will be documented in this file.

## [1.6.0] - 2025-04-19

### Added
- New `--no-traits` option for the `make:repository` command to generate repositories with complete implementation without using traits
- Fixed "Undefined property: App\Repositories\UserRepository::$model" error when using traits
- Improved repository structure using a single model property

### Improved
- Greater flexibility in repository generation with options for different implementation styles
- Consistent handling of model properties across all generated repositories
- Updated Spanish documentation with new options

## [1.5.0] - 2025-04-19

### Added
- Six specialized traits to make repositories more modular and maintainable:
  - `QueryableTrait`: For handling database queries
  - `RelationshipTrait`: For optimized relationship loading
  - `ScopableTrait`: For applying Eloquent scopes
  - `CrudOperationsTrait`: For basic CRUD operations
  - `PaginationTrait`: For different pagination methods
  - `TransactionTrait`: For database transaction handling
- Updated `make:repository` command to generate repositories using traits
- Comprehensive documentation for traits in README.traits.md
- Expanded configuration options in repository.php
- Support for cursor pagination via the new `cursorPaginate()` method
- English translations for all documentation

### Improved
- Enhanced modularity allowing developers to choose only needed functionality
- Better separation of concerns with specialized traits
- Code organization and maintainability
- Repository configuration with more customization options
- Performance optimizations for relationship loading and queries

## [1.4.0] - 2025-04-18

### Added
- New configuration file `config/repository.php` with customizable options
- Caching system for repository bindings to improve performance
- Improved `loadRelations()` method for optimizing relation loading
- Automatic detection of relations for using `withCount` when appropriate
- Support for database transactions
- Option to choose between direct updates or model-based updates

### Optimized
- Reduced memory usage in `createMany()` method
- Improved performance for queries with multiple relations
- Extracted condition logic to a helper method
- Implemented caching for repository registration to reduce ReflectionClass calls
- Enhanced `applyScopes()` method with support for global scopes
- Added N+1 problem detection when using scopes (optional, development only)

## [1.3.0] - 2025-04-16

### Added
- Support for Eloquent scopes in all query methods (all, find, findBy, findWhere, paginate, etc.)
- New `applyScopes()` method in BaseRepository for flexible scope application
- Documentation on how to use scopes with practical examples

## [1.2.0] - 2025-04-12

### Added
- Support for loading Eloquent relations in all query methods
- New methods for bulk operations: `createMany()`, `updateWhere()` and `deleteWhere()`
- Support for custom ordering via the `orderBy` parameter
- Support for computed attributes with the `appends` parameter in the `find()` method
- Strict typing in all methods with appropriate return types
- Improved handling of WHERE conditions, including custom operators and WHERE IN conditions
- Comprehensive documentation with detailed examples for each method

### Improved
- Better error handling in methods like `update()` and `delete()`
- Cleaner implementations using PHP 8 features like the nullsafe operator
- Documentation now includes practical examples in English and Spanish

## [1.1.0] - 2024-03-15

### Added
- New `--empty` option for the `make:repository` command to create repositories without predefined methods.

### Updated
- Documentation in README.md and README.es.md to include information about the new `--empty` option.

## [1.0.0] - Initial version

### Added
- Initial implementation of the repository pattern for Laravel.
- `make:repository` command to automatically generate repositories, contracts and bindings.
- Support for `--force` and `--abstract` options.
- Documentation in English and Spanish.