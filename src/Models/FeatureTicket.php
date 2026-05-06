<?php

namespace Squarhe\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use Squarhe\Subscription\Models\Concerns\Expires;

/**
 * FeatureTicket model.
 *
 * Represents a consumption ticket assigned to a subscriber for a
 * given feature. A ticket may expire and carry a number of charges.
 */
class FeatureTicket extends Model
{
    use Expires;

    /**
     * Mass assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'charges',
        'expired_at',
    ];

    /**
     * Relationship to the feature associated with this ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feature()
    {
        return $this->belongsTo(config('soulbscription.models.feature'));
    }

    /**
     * Polymorphic relationship to the subscriber model that owns this ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subscriber()
    {
        return $this->morphTo('subscriber');
    }
}
