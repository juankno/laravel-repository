<?php

namespace Juankno\Repository\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for handling relationships in repositories
 */
trait RelationshipTrait
{
    /**
     * Optimized loading of relations
     *
     * @param Builder $query
     * @param array $relations
     * @return Builder
     */
    protected function loadRelations($query, array $relations): Builder
    {
        if (empty($relations)) {
            return $query;
        }
        
        // Optimize relation loading
        $autoLoadCount = config('repository.relations.auto_load_count', true);
        $maxEagerRelations = config('repository.relations.max_eager_relations', 5);
        
        // Split normal relations and relations to count
        $eagerLoad = [];
        $withCountRelations = [];
        
        foreach ($relations as $relation) {
            $eagerLoad[] = $relation;
            
            // Detect potential relations for withCount if enabled
            if ($autoLoadCount) {
                $relationName = explode('.', $relation)[0];
                if (method_exists($this->model, $relationName)) {
                    try {
                        $relationType = $this->model->$relationName();
                        if (is_a($relationType, 'Illuminate\\Database\\Eloquent\\Relations\\HasMany') || 
                            is_a($relationType, 'Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany')) {
                            $withCountRelations[] = $relationName;
                        }
                    } catch (\Exception $e) {
                        // Ignore errors when trying to detect relation type
                    }
                }
            }
        }
        
        // Apply with or withCount depending on the number of relations
        if (count($eagerLoad) <= $maxEagerRelations) {
            $query->with($eagerLoad);
        } else {
            // If there are too many relations, load only the main ones
            $primaryRelations = array_slice($eagerLoad, 0, $maxEagerRelations);
            $query->with($primaryRelations);
        }
        
        // Apply withCount if there are detected relations
        if (!empty($withCountRelations)) {
            $query->withCount(array_unique($withCountRelations));
        }
        
        return $query;
    }
}