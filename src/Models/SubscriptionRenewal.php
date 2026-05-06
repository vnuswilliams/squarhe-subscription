<?php

namespace Squarhe\Subscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SubscriptionRenewal Model
 * 
 * Represents a renewal event in the subscription lifecycle.
 * Maintains a historical record of every time a subscription is renewed,
 * including whether it was renewed on time or was overdue.
 * 
 * This model provides audit trail functionality, allowing tracking of
 * subscription renewal history and identifying patterns of overdue renewals.
 * 
 * @package Squarhe\Subscription\Models
 * 
 * @property int $id Primary key
 * @property int $subscription_id The ID of the subscription being renewed
 * @property bool $renewal Always true, indicates this is a renewal event
 * @property bool $overdue Whether the renewal occurred after the expiration date
 * @property \Carbon\CarbonInterface $created_at Timestamp when the renewal occurred
 * @property \Carbon\CarbonInterface $updated_at Timestamp when the record was last updated
 * 
 * @method static \Illuminate\Database\Eloquent\Builder whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder whereRenewal($value)
 * @method static \Illuminate\Database\Eloquent\Builder whereOverdue($value)
 */
class SubscriptionRenewal extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast to native types.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'overdue' => 'boolean',
        'renewal' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'overdue',
        'renewal',
    ];

    /**
     * Get the subscription that was renewed.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * 
     * @example
     * $subscription = $renewal->subscription;
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(config('soulbscription.models.subscription'));
    }
}
