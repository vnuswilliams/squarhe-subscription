<?php

namespace Squarhe\Subscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Squarhe\Subscription\Models\Concerns\HandlesRecurrence;

/**
 * Feature Model
 *
 * Represents a subscription feature that can be associated with multiple plans
 * and tracked through feature tickets. Features support recurrence handling,
 * consumption limits, and time-based periodicity.
 *
 * @package Squarhe\Subscription\Models
 *
 * @property int $id The unique identifier
 * @property string $name The name of the feature
 * @property bool $consumable Whether the feature is consumable (usage-based)
 * @property string $periodicity_type The type of periodicity (e.g., 'monthly', 'yearly')
 * @property int $periodicity The periodicity duration value
 * @property int $quota The quota limit for this feature
 * @property bool $postpaid Whether the feature supports postpaid billing
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder whereConsumable($value)
 * @method static \Illuminate\Database\Eloquent\Builder whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder wherePeriodicityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder wherePeriodicity($value)
 * @method static \Illuminate\Database\Eloquent\Builder whereQuota($value)
 * @method static \Illuminate\Database\Eloquent\Builder wherePostpaid($value)
 */
class Feature extends Model
{
    use HandlesRecurrence;
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'consumable',
        'name',
        'periodicity_type',
        'periodicity',
        'quota',
        'postpaid',
    ];

    /**
     * Get the plans that have this feature.
     *
     * Retrieves all subscription plans associated with this feature
     * through the feature_plan pivot table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function plans()
    {
        return $this->belongsToMany(config('soulbscription.models.plan'))
            ->using(config('soulbscription.models.feature_plan'));
    }

    /**
     * Get the feature tickets for this feature.
     *
     * Retrieves all feature tickets associated with this feature,
     * representing individual feature usages or allocations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets()
    {
        return $this->hasMany(config('soulbscription.models.feature_ticket'));
    }
}
