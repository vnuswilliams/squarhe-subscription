<?php

namespace Squarhe\Subscription\Models\Concerns;

use Squarhe\Subscription\Models\Scopes\SuppressingScope;

/**
 * Adds suppression behavior and associated global scope.
 */
trait Suppresses
{
    /**
     * Registers the trait global scope.
     *
     * @return void
     */
    public static function bootSuppresses()
    {
        static::addGlobalScope(new SuppressingScope());
    }

    /**
     * Initializes casts required by the trait.
     *
     * @return void
     */
    public function initializeSuppresses()
    {
        if (! isset($this->casts['suppressed_at'])) {
            $this->casts['suppressed_at'] = 'datetime';
        }
    }

    public function suppressed()
    {
        if (empty($this->suppressed_at)) {
            return false;
        }

        return $this->suppressed_at->isPast();
    }

    public function notSuppressed()
    {
        return ! $this->suppressed();
    }
}
