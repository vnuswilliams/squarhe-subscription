<?php

namespace Squarhe\Subscription\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * ExpiringScope - Global Query Scope
 * 
 * Automatically filters queries to exclude expired records.
 * By default, only returns records where the expiration date is in the future
 * or has not been set. This scope is applied globally to any model that uses it.
 * 
 * This scope provides several useful extensions for customizing expiration queries:
 * - withExpired()      - Include expired records in results
 * - withoutExpired()   - Explicitly exclude expired records
 * - onlyExpired()      - Get only expired records
 * 
 * @package Squarhe\Subscription\Models\Scopes
 * 
 * @example
 * // Get only non-expired records (default behavior)
 * $features = Feature::all();
 * 
 * // Include expired records in results
 * $allFeatures = Feature::withExpired()->get();
 * 
 * // Get only expired records
 * $expiredFeatures = Feature::onlyExpired()->get();
 */
class ExpiringScope implements Scope
{
    /**
     * The list of macros to add to the query builder.
     * 
     * @var array<int, string>
     */
    protected $extensions = [
        'OnlyExpired',
        'WithExpired',
        'WithoutExpired',
    ];

    /**
     * Apply the scope to a given Eloquent query builder.
     * 
     * Filters the query to exclude expired records by default.
     * A record is considered non-expired if:
     * - expired_at is in the future, OR
     * - expired_at is NULL (no expiration)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $builder The query builder instance
     * @param \Illuminate\Database\Eloquent\Model $model The model being queried
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where(
            fn (Builder $query) =>
            $query->where('expired_at', '>', now())
                ->orWhereNull('expired_at')
        );
    }

    /**
     * Extend the query builder with custom macros for expiration queries.
     * 
     * Registers three macro extensions that allow fine-grained control
     * over expiration filtering.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $builder The query builder instance
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    /**
     * Add the withExpired macro to the query builder.
     * 
     * Registers a macro that allows including or excluding expired records
     * from the query results. When called without arguments or with true,
     * it includes all records regardless of expiration status.
     * 
     * Usage:
     * - Feature::withExpired()->get()        // Include expired
     * - Feature::withExpired(true)->get()    // Include expired
     * - Feature::withExpired(false)->get()   // Exclude expired
     * 
     * @param \Illuminate\Database\Eloquent\Builder $builder The query builder instance
     * @return void
     */
    protected function addWithExpired(Builder $builder)
    {
        $builder->macro('withExpired', function (Builder $builder, $withExpired = true) {
            if ($withExpired) {
                return $builder->withoutGlobalScope($this);
            }

            return $builder->withoutExpired();
        });
    }

    /**
     * Add the withoutExpired macro to the query builder.
     * 
     * Registers a macro that explicitly excludes expired records
     * from the query results. This replicates the default behavior
     * but can be used for clarity in queries.
     * 
     * Usage:
     * - Feature::withoutExpired()->get()  // Only non-expired records
     * 
     * @param \Illuminate\Database\Eloquent\Builder $builder The query builder instance
     * @return void
     */
    protected function addWithoutExpired(Builder $builder)
    {
        $builder->macro('withoutExpired', function (Builder $builder) {
            $builder->withoutGlobalScope($this)->where(
                fn (Builder $query) =>
                $query->where('expired_at', '>', now())
                    ->orWhereNull('expired_at')
            );

            return $builder;
        });
    }

    /**
     * Add the onlyExpired macro to the query builder.
     * 
     * Registers a macro that retrieves only expired records.
     * A record is considered expired if:
     * - expired_at is not NULL AND
     * - expired_at is less than or equal to the current time
     * 
     * Usage:
     * - Feature::onlyExpired()->get()  // Only expired records
     * 
     * @param \Illuminate\Database\Eloquent\Builder $builder The query builder instance
     * @return void
     */
    protected function addOnlyExpired(Builder $builder)
    {
        $builder->macro('onlyExpired', function (Builder $builder) {
            $builder->withoutGlobalScope($this)->where(
                fn (Builder $query) =>
                $query->where('expired_at', '<=', now())
                    ->whereNotNull('expired_at')
            );

            return $builder;
        });
    }
}
