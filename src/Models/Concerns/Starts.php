<?php

namespace Squarhe\Subscription\Models\Concerns;

use Squarhe\Subscription\Models\Scopes\StartingScope;

/**
 * Adds start-date behavior and started/not-started scope behavior.
 */
trait Starts
{
    /**
     * Registers the trait global scope.
     *
     * @return void
     */
    public static function bootStarts()
    {
        static::addGlobalScope(new StartingScope());
    }

    /**
     * Initializes casts required by the trait.
     *
     * @return void
     */
    public function initializeStarts()
    {
        if (! isset($this->casts['started_at'])) {
            $this->casts['started_at'] = 'datetime';
        }
    }

    public function started()
    {
        if (empty($this->started_at)) {
            return false;
        }

        return $this->started_at->isPast();
    }

    public function notStarted()
    {
        return ! $this->started();
    }
}
