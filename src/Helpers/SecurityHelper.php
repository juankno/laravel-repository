<?php

namespace Juankno\Repository\Helpers;

use Illuminate\Database\Eloquent\Model;

/**
 * Security helper for repository operations
 */
class SecurityHelper
{
    /**
     * Validate column name to prevent SQL injection
     *
     * @param string $column
     * @return bool
     */
    public static function isValidColumnName(string $column): bool
    {
        // Check if validation is disabled
        if (!config('repository.security.validate_column_names', true)) {
            return true;
        }

        // Allow only alphanumeric characters, underscores, dots (for table.column), and asterisk (for *)
        // This prevents SQL injection through column names
        return preg_match('/^[a-zA-Z0-9_.*]+$/', $column) === 1;
    }

    /**
     * Validate multiple column names
     *
     * @param array $columns
     * @return bool
     */
    public static function areValidColumnNames(array $columns): bool
    {
        foreach ($columns as $column) {
            if (!self::isValidColumnName($column)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Validate ORDER BY direction
     *
     * @param string $direction
     * @return bool
     */
    public static function isValidOrderDirection(string $direction): bool
    {
        $allowedDirections = config('repository.security.allowed_order_directions', ['asc', 'desc', 'ASC', 'DESC']);
        
        return in_array($direction, $allowedDirections, true);
    }

    /**
     * Sanitize column name (remove potentially dangerous characters)
     *
     * @param string $column
     * @return string
     */
    public static function sanitizeColumnName(string $column): string
    {
        // Remove anything that's not alphanumeric, underscore, dot, or asterisk
        return preg_replace('/[^a-zA-Z0-9_.*]/', '', $column);
    }

    /**
     * Validate column against model's fillable attributes (strict mode)
     *
     * @param Model $model
     * @param string $column
     * @return bool
     */
    public static function isColumnInModelFillable(Model $model, string $column): bool
    {
        // If strict validation is disabled, allow all valid column names
        if (!config('repository.security.strict_column_validation', false)) {
            return self::isValidColumnName($column);
        }

        // Special case: allow * for SELECT
        if ($column === '*') {
            return true;
        }

        // Get fillable attributes
        $fillable = $model->getFillable();
        
        // If fillable is empty, check guarded instead
        if (empty($fillable)) {
            $guarded = $model->getGuarded();
            
            // If guarded is ['*'], nothing is fillable
            if (in_array('*', $guarded)) {
                return false;
            }
            
            // If not in guarded, it's allowed
            return !in_array($column, $guarded);
        }

        // Check if column is in fillable
        return in_array($column, $fillable) || $column === $model->getKeyName();
    }

    /**
     * Sanitize SQL query for logging (remove sensitive data)
     *
     * @param string $sql
     * @param array $bindings
     * @return string
     */
    public static function sanitizeSqlForLogging(string $sql, array $bindings = []): string
    {
        if (!config('repository.security.sanitize_logs', true)) {
            return $sql;
        }

        // Replace actual values with placeholders to avoid logging sensitive data
        $sanitized = $sql;
        
        // Replace string bindings with [REDACTED]
        foreach ($bindings as $binding) {
            if (is_string($binding)) {
                $sanitized = preg_replace('/[\'"]' . preg_quote($binding, '/') . '[\'"]/', "'[REDACTED]'", $sanitized, 1);
            }
        }

        return $sanitized;
    }

    /**
     * Get maximum results limit
     *
     * @return int (0 means no limit)
     */
    public static function getMaxResultsLimit(): int
    {
        return config('repository.security.max_results_limit', 10000);
    }

    /**
     * Validate that results count doesn't exceed limit
     *
     * @param int $requestedCount
     * @return int Safe count to use
     */
    public static function getSafeResultsLimit(?int $requestedCount = null): int
    {
        $maxLimit = self::getMaxResultsLimit();
        
        // If no limit is set (0), return requested count or no limit
        if ($maxLimit === 0) {
            return $requestedCount ?? 0;
        }

        // If no specific count requested, return max limit
        if ($requestedCount === null) {
            return $maxLimit;
        }

        // Return the smaller of the two
        return min($requestedCount, $maxLimit);
    }
}
