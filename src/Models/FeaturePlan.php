<?php

namespace Squarhe\Subscription\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * FeaturePlan pivot model.
 *
 * Pivot model linking plans and features with the configured
 * charges for that feature within a plan.
 */
class FeaturePlan extends Pivot
{
    /**
     * Mass assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'charges',
    ];

    /**
     * Relationship to the pivot's feature.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feature()
    {
        return $this->belongsTo(config('soulbscription.models.feature'));
    }

    /**
     * Relationship to the pivot's plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan()
    {
        return $this->belongsTo(config('soulbscription.models.plan'));
    }
}
