<?php

namespace Juankno\Repository\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for handling model scopes in repositories
 */
trait ScopableTrait
{
    /**
     * Apply scopes to the query builder
     *
     * @param Builder $query
     * @param array $scopes Array of scope names or callables to apply
     * @return Builder
     */
    protected function applyScopes($query, array $scopes = []): Builder
    {
        // Apply global scopes from configuration if any
        $globalScopes = config('repository.scopes.global', []);
        foreach ($globalScopes as $globalScope) {
            if (is_string($globalScope) && method_exists($this->model, 'scope' . ucfirst($globalScope))) {
                $query->$globalScope();
            }
        }
        
        // Apply scopes provided to the method
        foreach ($scopes as $scope) {
            if (is_string($scope)) {
                // Apply named scope defined in the model
                $query->$scope();
            } elseif (is_callable($scope)) {
                // Apply closure scope
                $scope($query);
            } elseif (is_array($scope) && count($scope) >= 1) {
                // Apply scope with parameters - first element is scope name, rest are parameters
                $method = array_shift($scope);
                $query->$method(...$scope);
            }
            
            // Detect N+1 problems if enabled and in debug mode
            if (config('app.debug') && config('repository.scopes.detect_n_plus_one', false)) {
                $scopeName = is_string($scope) ? $scope : 'closure_scope';
                \Illuminate\Support\Facades\DB::listen(function($query) use ($scopeName) {
                    if (strpos($query->sql, 'select') === 0) {
                        info("[Repository N+1 Detection] Scope: {$scopeName} - Query: {$query->sql}");
                    }
                });
            }
        }
        
        return $query;
    }
}