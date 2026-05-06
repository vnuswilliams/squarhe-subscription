<?php

namespace Squarhe\Subscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Squarhe\Subscription\Models\Concerns\Expires;

/**
 * FeatureConsumption model.
 *
 * Stores the actual consumption of a feature for a subscriber.
 * Acts as usage tracking and can be time-limited by an expiration date.
 */
class FeatureConsumption extends Model
{
    use Expires;
    use HasFactory;

    /**
     * Mass assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'consumption',
        'expired_at',
    ];

    /**
     * Relationship to the consumed feature.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feature()
    {
        return $this->belongsTo(config('soulbscription.models.feature'));
    }

    /**
     * Polymorphic relationship to the subscriber consuming the feature.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subscriber()
    {
        return $this->morphTo('subscriber');
    }
}
