<?php

namespace Squarhe\Subscription\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Squarhe\Subscription\Models\Concerns\HandlesRecurrence;

/**
 * Plan model.
 *
 * Defines a subscription offer (name, periodicity, grace days)
 * and the features available through a many-to-many relationship.
 */
class Plan extends Model
{
    use HandlesRecurrence;
    use HasFactory;
    use SoftDeletes;

    /**
     * Mass assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'grace_days',
        'name',
        'periodicity_type',
        'periodicity',
    ];

    /**
     * Features included in this plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function features()
    {
        return $this->belongsToMany(config('soulbscription.models.feature'))
            ->using(config('soulbscription.models.feature_plan'))
            ->withPivot(['charges']);
    }

    /**
     * Subscriptions using this plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany(config('soulbscription.models.subscription'));
    }

    /**
     * Calculates the grace period end date from a recurrence end date.
     *
     * @param CarbonInterface $recurrenceEnd
     * @return CarbonInterface
     */
    public function calculateGraceDaysEnd(CarbonInterface $recurrenceEnd)
    {
        return $recurrenceEnd->copy()->addDays($this->grace_days);
    }

    /**
     * Indicates whether the plan has a grace period.
     *
     * @return bool
     */
    public function getHasGraceDaysAttribute()
    {
        return ! empty($this->grace_days);
    }
}
