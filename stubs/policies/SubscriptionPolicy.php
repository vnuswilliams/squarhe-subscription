<?php

namespace App\Policies;

use Illuminate\Database\Eloquent\Model;
use Squarhe\Subscription\Models\Subscription;

/**
 * Authorization policy for subscription-related actions.
 *
 * This policy centralizes ownership checks and guards write operations
 * (renew, cancel, feature consumption) based on the current subscription state.
 */
class SubscriptionPolicy
{
    /**
     * Determine whether the user can view any subscriptions.
     */
    public function viewAny(Model $user): bool
    {
        return true;
    
    }

    /**
     * Determine whether the user can view the given subscription.
     */
    public function view(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription);
    }

    /**
     * Determine whether the user can create a subscription.
     */
    public function create(Model $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the given subscription.
     */
    public function update(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription);
    }

    /**
     * Determine whether the user can delete the given subscription.
     */
    public function delete(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription);
    }

    /**
     * Determine whether the user can restore the given subscription.
     */
    public function restore(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription);
    }

    /**
     * Determine whether the user can permanently delete the subscription.
     *
     * Force delete is intentionally disabled by default.
     */
    public function forceDelete(Model $user, Subscription $subscription): bool
    {
        return false;
    }

    /**
     * Determine whether the user can renew the given subscription.
     *
     * Renewal is allowed only for the owner and for non-canceled subscriptions.
     */
    public function renew(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription)
            && ! $subscription->canceled_at;
    }

    /**
     * Determine whether the user can cancel the given subscription.
     *
     * Cancel is allowed only for the owner and for non-canceled subscriptions.
     */
    public function cancel(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription)
            && ! $subscription->canceled_at;
    }

    /**
     * Determine whether the user can suppress the given subscription.
     */
    public function suppress(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription);
    }

    /**
     * Determine whether the user can consume a feature from this subscription.
     *
     * Requires ownership, an active (non-canceled) subscription, and enough
     * available charges according to the subscriber feature rules.
     */
    public function consumeFeature(Model $user, Subscription $subscription, string $featureName, ?float $consumption = null): bool
    {
        return $this->ownsSubscription($user, $subscription)
            && ! $subscription->canceled_at
            && $subscription->subscriber->canConsume($featureName, $consumption);
    }

    /**
     * Determine whether the user can set consumed quota for a feature.
     *
     * Requires ownership, an active (non-canceled) subscription, feature access,
     * and a quota value that does not exceed total available charges.
     */
    public function setFeatureConsumedQuota(Model $user, Subscription $subscription, string $featureName, float $consumption): bool
    {
        return $this->ownsSubscription($user, $subscription)
            && ! $subscription->canceled_at
            && $subscription->subscriber->hasFeature($featureName)
            && $subscription->subscriber->getTotalCharges($featureName) >= $consumption;
    }

    /**
     * Determine whether the given user owns the subscription.
     *
     * Ownership is validated against the subscription polymorphic subscriber
     * identifier and morph class.
     */
    private function ownsSubscription(Model $user, Subscription $subscription): bool
    {
        return $subscription->subscriber_id === $user->getKey()
            && $subscription->subscriber_type === $user->getMorphClass();
    }
}
