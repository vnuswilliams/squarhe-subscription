<?php

namespace Squarhe\Subscription\Policies;

use Illuminate\Database\Eloquent\Model;
use Squarhe\Subscription\Models\Subscription;

class SubscriptionPolicy
{
    public function viewAny(Model $user): bool
    {
        return true;
    }

    public function view(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription);
    }

    public function create(Model $user): bool
    {
        return true;
    }

    public function update(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription);
    }

    public function delete(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription);
    }

    public function restore(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription);
    }

    public function forceDelete(Model $user, Subscription $subscription): bool
    {
        return false;
    }

    public function renew(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription)
            && ! $subscription->canceled_at;
    }

    public function cancel(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription)
            && ! $subscription->canceled_at;
    }

    public function suppress(Model $user, Subscription $subscription): bool
    {
        return $this->ownsSubscription($user, $subscription);
    }

    private function ownsSubscription(Model $user, Subscription $subscription): bool
    {
        return $subscription->subscriber_id === $user->getKey()
            && $subscription->subscriber_type === $user->getMorphClass();
    }
}
