<?php

namespace Squarhe\Subscription\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Squarhe\Subscription\Models\Subscription;

/**
 * Event dispatched when a subscription is suppressed.
 */
class SubscriptionSuppressed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param Subscription $subscription
     */
    public function __construct(
        public Subscription $subscription,
    ) {
        //
    }
}
