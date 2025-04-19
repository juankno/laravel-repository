<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Repository Cache
    |--------------------------------------------------------------------------
    |
    | This configuration controls the caching behavior of repository bindings
    | to improve performance.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 60, // Time in minutes that bindings will be cached
        'key' => 'laravel_repository_bindings',
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
    | Relation Loading Options
    |--------------------------------------------------------------------------
    |
    | Configure how repositories will load relations.
    |
    */
    'relations' => [
        'auto_load_count' => true, // Automatically add withCount for hasMany/belongsToMany relations
        'max_eager_relations' => 5, // Maximum number of relations for eager loading before using lazy loading
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
];