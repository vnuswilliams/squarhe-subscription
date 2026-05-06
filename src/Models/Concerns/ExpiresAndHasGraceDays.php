<?php

namespace Squarhe\Subscription\Models\Concerns;

use Squarhe\Subscription\Models\Scopes\ExpiringWithGraceDaysScope;

/**
 * Handles expiration with grace period (casts, global scope, helpers).
 */
trait ExpiresAndHasGraceDays
{
    /**
     * Registers the trait global scope.
     *
     * @return void
     */
    public static function bootExpiresAndHasGraceDays()
    {
        static::addGlobalScope(new ExpiringWithGraceDaysScope());
    }

    /**
     * Initializes casts required by the trait.
     *
     * @return void
     */
    public function initializeExpiresAndHasGraceDays()
    {
        if (! isset($this->casts['expired_at'])) {
            $this->casts['expired_at'] = 'datetime';
        }

        if (! isset($this->casts['grace_days_ended_at'])) {
            $this->casts['grace_days_ended_at'] = 'datetime';
        }
    }

    /**
     * Indicates whether the model is expired.
     *
     * @return bool
     */
    public function expired()
    {
        if (is_null($this->expired_at)) {
            return false;
        }

        if (is_null($this->grace_days_ended_at)) {
            return $this->expired_at->isPast();
        }

        return $this->expired_at->isPast()
            and $this->grace_days_ended_at->isPast();
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

    public function hasExpired()
    {
        return $this->expired();
    }

    public function hasNotExpired()
    {
        return $this->notExpired();
    }
}
