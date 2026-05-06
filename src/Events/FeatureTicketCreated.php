<?php

namespace Squarhe\Subscription\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Squarhe\Subscription\Models\Feature;
use Squarhe\Subscription\Models\FeatureTicket;

/**
 * Event dispatched after a feature ticket is created.
 */
class FeatureTicketCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param mixed $subscriber
     * @param Feature $feature
     * @param FeatureTicket $featureTicket
     */
    public function __construct(
        public $subscriber,
        public Feature $feature,
        public FeatureTicket $featureTicket,
    ) {
        //
    }
}
