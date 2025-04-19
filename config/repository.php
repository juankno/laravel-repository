<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Repository Cache
    |--------------------------------------------------------------------------
    |
    | This configuration controls the caching behavior of repositories
    | to improve performance of queries.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 60, // Time in minutes that results will be cached
        'key_prefix' => 'laravel_repository_',
        'driver' => null, // null = use default driver, 'redis', 'file', etc.
        'skip_in_development' => true, // Don't use cache in development (APP_ENV=local)
    ],

    /*
    |--------------------------------------------------------------------------
    | Repository Directories
    |--------------------------------------------------------------------------
    |
    | Here you can define additional directories to look for repositories
    | besides the main 'app/Repositories' directory.
    |
    */
    'directories' => [
        // app_path('Repositories'), // This is the default directory
        // app_path('Domain/Repositories'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Repository Structure
    |--------------------------------------------------------------------------
    |
    | Configure the folder structure for repositories and interfaces/contracts.
    |
    */
    'structure' => [
        'interfaces_folder' => 'Contracts', // Can be 'Contracts' or 'Interfaces' or any custom folder name
        'validate_interface_folders' => true, // Check if both potential interface folders exist before creating
    ],

    /*
    |--------------------------------------------------------------------------
    | Relation Loading Options
    |--------------------------------------------------------------------------
    |
    | Configure how repositories will load relations.
    |
    */
    'relations' => [
        'auto_load_count' => true, // Automatically add withCount for hasMany/belongsToMany relations
        'max_eager_relations' => 5, // Maximum number of relations for eager loading before using lazy loading
        'allow_nested_relations' => true, // Allow loading nested relations with dot notation (e.g. 'posts.comments')
        'debug_relations' => false, // Log relation queries for debugging
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Optimization
    |--------------------------------------------------------------------------
    |
    | Options to optimize queries in repositories.
    |
    */
    'query' => [
        'use_direct_update' => true, // Use direct update instead of find + update
        'use_direct_delete' => true, // Use direct delete instead of find + delete
        'chunk_size' => 1000, // Chunk size for large queries
        'optimize_selects' => true, // Only select necessary columns when possible
    ],

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Configuration for scope behavior.
    |
    */
    'scopes' => [
        'global' => [
            // Global scopes to apply to all repositories
            // Example: 'active', 'visible'
        ],
        'detect_n_plus_one' => false, // Detect potential N+1 problems in scopes (development only)
        'auto_apply' => [], // Scopes that will be automatically applied to all queries
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Traits
    |--------------------------------------------------------------------------
    |
    | Configuration for repository traits.
    |
    */
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
        'namespace' => 'Juankno\\Repository\\Traits',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Bindings
    |--------------------------------------------------------------------------
    |
    | List of common bindings that will be registered automatically.
    |
    */
    'bindings' => [
        'User' => [
            'interface' => 'App\\Repositories\\Contracts\\UserRepositoryInterface',
            'implementation' => 'App\\Repositories\\UserRepository',
        ],
        'Auth' => [
            'interface' => 'App\\Repositories\\Contracts\\AuthRepositoryInterface',
            'implementation' => 'App\\Repositories\\AuthRepository',
        ],
        'Post' => [
            'interface' => 'App\\Repositories\\Contracts\\PostRepositoryInterface',
            'implementation' => 'App\\Repositories\\PostRepository',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Code Generation
    |--------------------------------------------------------------------------
    |
    | Options for code generation when creating repositories.
    |
    */
    'generation' => [
        'add_docblocks' => true, // Add documentation blocks to generated methods
        'use_types' => true, // Use PHP 7.4+ types in generated methods
        'use_return_types' => true, // Use PHP 7.4+ return types
        'default_model_namespace' => 'App\\Models', // Default namespace for models
        'repository_namespace' => 'App\\Repositories', // Default namespace for repositories
        'contract_namespace' => 'App\\Repositories\\Contracts', // Default namespace for interfaces
    ],
];