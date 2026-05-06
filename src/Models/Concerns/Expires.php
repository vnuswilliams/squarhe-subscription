<?php

namespace Squarhe\Subscription\Models\Concerns;

use Squarhe\Subscription\Models\Scopes\ExpiringScope;

/**
 * Handles simple model expiration (casts, global scope, helpers).
 */
trait Expires
{
    /**
     * Registers the trait global scope.
     *
     * @return void
     */
    public static function bootExpires()
    {
        static::addGlobalScope(new ExpiringScope());
    }

    /**
     * Initializes casts required by the trait.
     *
     * @return void
     */
    public function initializeExpires()
    {
        if (! isset($this->casts['expired_at'])) {
            $this->casts['expired_at'] = 'datetime';
        }
    }

    /**
     * Indicates whether the model is expired.
     *
     * @return bool
     */
    public function expired()
    {
        return $this->expired_at->isPast();
    }

    /**
     * Indicates whether the model is not expired.
     *
     * @return bool
     */
    public function notExpired()
    {
        return ! $this->expired();
    }
}
