<?php

namespace Juankno\Repository\Traits;

use Illuminate\Database\Eloquent\Builder;
use Juankno\Repository\Helpers\SecurityHelper;

/**
 * Trait for handling query operations in repositories
 */
trait QueryableTrait
{
    /**
     * Apply conditions efficiently
     *
     * @param Builder $query
     * @param array $conditions
     * @return void
     * @throws \InvalidArgumentException If column name is invalid
     */
    protected function applyConditions($query, array $conditions): void
    {
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                if (count($value) === 3) {
                    list($field, $operator, $searchValue) = $value;
                    
                    // Validate column name to prevent SQL injection
                    if (!SecurityHelper::isValidColumnName($field)) {
                        throw new \InvalidArgumentException("Invalid column name: {$field}");
                    }
                    
                    $query->where($field, $operator, $searchValue);
                } else {
                    // Validate column name for whereIn
                    if (!SecurityHelper::isValidColumnName($field)) {
                        throw new \InvalidArgumentException("Invalid column name: {$field}");
                    }
                    
                    $query->whereIn($field, $value);
                }
            } else {
                // Validate column name
                if (!SecurityHelper::isValidColumnName($field)) {
                    throw new \InvalidArgumentException("Invalid column name: {$field}");
                }
                
                $query->where($field, $value);
            }
        }
    }
    
    /**
     * Apply order by clauses to the query
     *
     * @param Builder $query
     * @param array $orderBy [column => direction]
     * @return Builder
     * @throws \InvalidArgumentException If column name or direction is invalid
     */
    protected function applyOrderBy($query, array $orderBy): Builder
    {
        if (!empty($orderBy)) {
            foreach ($orderBy as $column => $direction) {
                // Validate column name to prevent SQL injection
                if (!SecurityHelper::isValidColumnName($column)) {
                    throw new \InvalidArgumentException("Invalid column name for ORDER BY: {$column}");
                }
                
                // Validate direction to prevent SQL injection
                if (!SecurityHelper::isValidOrderDirection($direction)) {
                    throw new \InvalidArgumentException("Invalid ORDER BY direction: {$direction}");
                }
                
                $query->orderBy($column, $direction);
            }
        }
        
        return $query;
    }
    
    /**
     * Get the first record matching conditions
     */
    public function first(array $conditions = [], array $columns = ['*'], array $relations = [], array $orderBy = [], array $scopes = []): ?\Illuminate\Database\Eloquent\Model
    {
        $query = $this->model->select($columns);
        
        // Apply scopes if provided
        $query = $this->applyScopes($query, $scopes);
        
        // Load relations 
        $query = $this->loadRelations($query, $relations);
        
        if (!empty($conditions)) {
            $this->applyConditions($query, $conditions);
        }
        
        $this->applyOrderBy($query, $orderBy);
        
        return $query->first();
    }
    
    /**
     * Find records matching conditions
     */
    public function findWhere(array $conditions, array $columns = ['*'], array $relations = [], array $orderBy = [], array $scopes = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->model->select($columns);
        
        // Apply scopes if provided
        $query = $this->applyScopes($query, $scopes);
        
        // Load relations 
        $query = $this->loadRelations($query, $relations);
        
        // Apply conditions efficiently
        $this->applyConditions($query, $conditions);
        
        $this->applyOrderBy($query, $orderBy);
        
        return $query->get();
    }
    
    /**
     * Find a record by a specific field
     */
    public function findBy(string $field, mixed $value, array $columns = ['*'], array $relations = [], array $scopes = []): ?\Illuminate\Database\Eloquent\Model
    {
        // Validate field name to prevent SQL injection
        if (!SecurityHelper::isValidColumnName($field)) {
            throw new \InvalidArgumentException("Invalid column name: {$field}");
        }
        
        // Validate column names
        if (!SecurityHelper::areValidColumnNames($columns)) {
            throw new \InvalidArgumentException("Invalid column names in select");
        }
        
        $query = $this->model->select($columns);
        
        // Apply scopes if provided
        $query = $this->applyScopes($query, $scopes);
        
        // Load relations 
        $query = $this->loadRelations($query, $relations);
        
        return $query->where($field, $value)->first();
    }
}