<?php

namespace Squarhe\Subscription\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Squarhe\Subscription\Models\Feature;
use Squarhe\Subscription\Models\FeatureConsumption;

/**
 * Event dispatched after a feature is consumed.
 */
class FeatureConsumed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param mixed $subscriber
     * @param Feature $feature
     * @param FeatureConsumption $featureConsumption
     */
    public function __construct(
        public $subscriber,
        public Feature $feature,
        public FeatureConsumption $featureConsumption,
    ) {
        //
    }
}
