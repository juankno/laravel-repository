<?php

namespace Juankno\Repository\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait for handling CRUD operations in repositories
 */
trait CrudOperationsTrait
{
    /**
     * Get all records
     */
    public function all(array $columns = ['*'], array $relations = [], array $orderBy = [], array $scopes = []): Collection
    {
        $query = $this->model->select($columns);
        
        // Apply scopes if provided
        $query = $this->applyScopes($query, $scopes);
        
        // Load relations
        $query = $this->loadRelations($query, $relations);
        
        // Apply order by
        $this->applyOrderBy($query, $orderBy);
        
        return $query->get();
    }
    
    /**
     * Find a record by ID
     */
    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = [], array $scopes = []): ?Model
    {
        $query = $this->model->select($columns);
        
        // Apply scopes if provided
        $query = $this->applyScopes($query, $scopes);
        
        // Load relations
        $query = $this->loadRelations($query, $relations);
        
        $model = $query->find($id);
        
        if ($model && !empty($appends)) {
            $model->append($appends);
        }
        
        return $model;
    }
    
    /**
     * Create a new record
     */
    public function create(array $data): ?Model
    {
        return $this->model->create($data);
    }
    
    /**
     * Update an existing record
     */
    public function update(int $id, array $data): Model|bool
    {
        // Use direct update or find + update according to configuration
        $useDirectUpdate = config('repository.query.use_direct_update', true);
        
        if ($useDirectUpdate) {
            $affected = $this->model->where('id', $id)->update($data);
            
            if ($affected) {
                return $this->find($id);
            }
            
            return false;
        }
        
        // Previous method (find + update)
        $model = $this->find($id);
        
        if (!$model) {
            return false;
        }
        
        $result = $model->update($data);
        
        // Return the updated model or false if it fails
        return $result ? $model->fresh() : false;
    }
    
    /**
     * Delete a record
     */
    public function delete(int $id): bool
    {
        // Use direct delete or find + delete according to configuration
        $useDirectDelete = config('repository.query.use_direct_delete', true);
        
        if ($useDirectDelete) {
            return $this->model->where('id', $id)->delete() > 0;
        }
        
        // Previous method (find + delete)
        $model = $this->find($id);
        return $model ? $model->delete() : false;
    }
    
    /**
     * Create multiple records in a single operation
     */
    public function createMany(array $data): Collection
    {
        // Optimized to use less memory and be more efficient
        return collect($data)->map(function($item) {
            return $this->create($item);
        });
    }
    
    /**
     * Update records in bulk based on conditions
     */
    public function updateWhere(array $conditions, array $data, array $scopes = []): bool
    {
        $query = $this->model->query();
        
        // Apply scopes if provided
        $query = $this->applyScopes($query, $scopes);
        
        if (!empty($conditions)) {
            $this->applyConditions($query, $conditions);
        }
        
        return $query->update($data);
    }
    
    /**
     * Delete records in bulk based on conditions
     */
    public function deleteWhere(array $conditions, array $scopes = []): bool|int
    {
        $query = $this->model->query();
        
        // Apply scopes if provided
        $query = $this->applyScopes($query, $scopes);
        
        if (!empty($conditions)) {
            $this->applyConditions($query, $conditions);
        }
        
        return $query->delete();
    }
}