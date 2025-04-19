<?php

namespace Juankno\Repository\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Trait for handling database transactions in repositories
 */
trait TransactionTrait
{
    /**
     * Begin a new database transaction
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }
    
    /**
     * Commit the active database transaction
     *
     * @return void
     */
    public function commit(): void
    {
        DB::commit();
    }
    
    /**
     * Rollback the active database transaction
     *
     * @return void
     */
    public function rollBack(): void
    {
        DB::rollBack();
    }
    
    /**
     * Execute a callback within a transaction
     *
     * @param  callable  $callback
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(callable $callback)
    {
        return DB::transaction($callback);
    }
}