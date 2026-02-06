<?php

namespace Juankno\Repository\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Juankno\Repository\Helpers\SecurityHelper;

/**
 * Trait for handling pagination in repositories
 */
trait PaginationTrait
{
    /**
     * Paginate records
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = [], array $orderBy = [], array $conditions = [], array $scopes = []): LengthAwarePaginator
    {
        // Validate column names to prevent SQL injection
        if (!SecurityHelper::areValidColumnNames($columns)) {
            throw new \InvalidArgumentException("Invalid column names in select");
        }
        
        $query = $this->model->select($columns);
        
        // Apply scopes if provided
        $query = $this->applyScopes($query, $scopes);
        
        // Load relations
        $query = $this->loadRelations($query, $relations);
        
        if (!empty($conditions)) {
            $this->applyConditions($query, $conditions);
        }
        
        // Apply order by
        $this->applyOrderBy($query, $orderBy);
        
        return $query->paginate($perPage);
    }
    
    /**
     * Cursor paginate records for efficient pagination of large datasets
     *
     * @param int $perPage Records per page
     * @param array $columns Columns to select
     * @param array $relations Relations to load
     * @param array $orderBy Order columns [column => direction]
     * @param array $conditions Conditions to filter
     * @param array $scopes Array of scope names or callables to apply
     * @return \Illuminate\Pagination\CursorPaginator
     */
    public function cursorPaginate(int $perPage = 15, array $columns = ['*'], array $relations = [], array $orderBy = [], array $conditions = [], array $scopes = [])
    {
        // Validate column names to prevent SQL injection
        if (!SecurityHelper::areValidColumnNames($columns)) {
            throw new \InvalidArgumentException("Invalid column names in select");
        }
        
        $query = $this->model->select($columns);
        
        // Apply scopes if provided
        $query = $this->applyScopes($query, $scopes);
        
        // Load relations
        $query = $this->loadRelations($query, $relations);
        
        if (!empty($conditions)) {
            $this->applyConditions($query, $conditions);
        }
        
        // Apply order by - critical for cursor pagination
        $this->applyOrderBy($query, $orderBy);
        
        return $query->cursorPaginate($perPage);
    }
    
    /**
     * Simple paginate records (lighter than LengthAwarePaginator)
     *
     * @param int $perPage Records per page
     * @param array $columns Columns to select
     * @param array $relations Relations to load
     * @param array $orderBy Order columns [column => direction]
     * @param array $conditions Conditions to filter
     * @param array $scopes Array of scope names or callables to apply
     * @return \Illuminate\Pagination\Paginator
     */
    public function simplePaginate(int $perPage = 15, array $columns = ['*'], array $relations = [], array $orderBy = [], array $conditions = [], array $scopes = [])
    {
        // Validate column names to prevent SQL injection
        if (!SecurityHelper::areValidColumnNames($columns)) {
            throw new \InvalidArgumentException("Invalid column names in select");
        }
        
        $query = $this->model->select($columns);
        
        // Apply scopes if provided
        $query = $this->applyScopes($query, $scopes);
        
        // Load relations
        $query = $this->loadRelations($query, $relations);
        
        if (!empty($conditions)) {
            $this->applyConditions($query, $conditions);
        }
        
        // Apply order by
        $this->applyOrderBy($query, $orderBy);
        
        return $query->simplePaginate($perPage);
    }
}