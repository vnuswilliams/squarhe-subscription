<?php

namespace Squarhe\Subscription\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Squarhe\Subscription\Events\SubscriptionCanceled;
use Squarhe\Subscription\Events\SubscriptionRenewed;
use Squarhe\Subscription\Events\SubscriptionScheduled;
use Squarhe\Subscription\Events\SubscriptionStarted;
use Squarhe\Subscription\Events\SubscriptionSuppressed;
use Squarhe\Subscription\Models\Concerns\ExpiresAndHasGraceDays;
use Squarhe\Subscription\Models\Concerns\Starts;
use Squarhe\Subscription\Models\Concerns\Suppresses;
use Squarhe\Subscription\Models\Scopes\ExpiringWithGraceDaysScope;
use Squarhe\Subscription\Models\Scopes\StartingScope;
use Squarhe\Subscription\Models\Scopes\SuppressingScope;

/**
 * Subscription model.
 *
 * Represents a subscriber's active (or historical) subscription to a plan.
 * Stores lifecycle dates (start, expiration, grace, suppression, cancellation)
 * and exposes lifecycle business operations (start, renew, cancel, suppress).
 */
class Subscription extends Model
{
    use ExpiresAndHasGraceDays;
    use HasFactory;
    use SoftDeletes;
    use Starts;
    use Suppresses;

    /**
     * Dates automatically cast to Carbon objects.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'canceled_at',
    ];

    /**
     * Mass assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'canceled_at',
        'expired_at',
        'grace_days_ended_at',
        'started_at',
        'suppressed_at',
        'was_switched',
    ];

    /**
     * Plan associated with this subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan()
    {
        return $this->belongsTo(config('soulbscription.models.plan'));
    }

    /**
     * Renewal history for this subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function renewals()
    {
        return $this->hasMany(config('soulbscription.models.subscription_renewal'));
    }

    /**
     * Polymorphic subscriber that owns the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subscriber()
    {
        return $this->morphTo('subscriber');
    }

    /**
     * Scope for non-active subscriptions (expired, not started, or suppressed).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeNotActive(Builder $query)
    {
        return $query->withoutGlobalScopes([
                ExpiringWithGraceDaysScope::class,
                StartingScope::class,
                SuppressingScope::class,
            ])
            ->where(function (Builder $query) {
                $query->where(fn (Builder $query) => $query->onlyExpired())
                    ->orWhere(fn (Builder $query) => $query->onlyNotStarted())
                    ->orWhere(fn (Builder $query) => $query->onlySuppressed());
            });
    }

    /**
     * Scope for canceled subscriptions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeCanceled(Builder $query)
    {
        return $query->whereNotNull('canceled_at');
    }

    /**
     * Scope for non-canceled subscriptions.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeNotCanceled(Builder $query)
    {
        return $query->whereNull('canceled_at');
    }

    /**
     * Marks the subscription as resulting from a plan switch.
     *
     * @return self
     */
    public function markAsSwitched(): self
    {
        return $this->fill([
            'was_switched' => true,
        ]);
    }

    /**
     * Starts the subscription at the given date and dispatches the appropriate event.
     *
     * @param CarbonInterface|null $startDate
     * @return self
     */
    public function start(?CarbonInterface $startDate = null): self
    {
        $startDate = $startDate ?: today();

        $this->fill(['started_at' => $startDate])
            ->save();

        if ($startDate->isToday()) {
            event(new SubscriptionStarted($this));
        } elseif ($startDate->isFuture()) {
            event(new SubscriptionScheduled($this));
        }

        return $this;
    }

    /**
     * Renews the subscription and recalculates expiration and grace period.
     *
     * @param CarbonInterface|null $expirationDate
     * @return self
     */
    public function renew(?CarbonInterface $expirationDate = null): self
    {
        $this->renewals()->create([
            'renewal' => true,
            'overdue' => $this->isOverdue,
        ]);

        $expirationDate = $this->getRenewedExpiration($expirationDate);
        $graceDaysEndedAt = null;

        if ($this->plan->grace_days && $expirationDate) {
            $graceDaysEndedAt = $expirationDate->copy()->addDays($this->plan->grace_days);
        }

        $this->update([
            'expired_at' => $expirationDate,
            'grace_days_ended_at' => $graceDaysEndedAt,
        ]);

        event(new SubscriptionRenewed($this));

        return $this;
    }

    /**
     * Cancels the subscription at the given date.
     *
     * @param CarbonInterface|null $cancelDate
     * @return self
     */
    public function cancel(?CarbonInterface $cancelDate = null): self
    {
        $cancelDate = $cancelDate ?: now();

        $this->fill(['canceled_at' => $cancelDate])
            ->save();

        event(new SubscriptionCanceled($this));

        return $this;
    }

    /**
     * Suppresses the subscription at the given date.
     *
     * @param CarbonInterface|null $suppressation
     * @return self
     */
    public function suppress(?CarbonInterface $suppressation = null)
    {
        $suppressationDate = $suppressation ?: now();

        $this->fill(['suppressed_at' => $suppressationDate])
            ->save();

        event(new SubscriptionSuppressed($this));

        return $this;
    }

    /**
     * Determines whether the subscription is overdue.
     *
     * @return bool
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->grace_days_ended_at) {
            return $this->expired_at->isPast()
                and $this->grace_days_ended_at->isPast();
        }

        if (! $this->expired_at) {
            return false;
        }

        return $this->expired_at->isPast();
    }

    /**
     * Determines the next expiration date during renewal.
     *
     * @param CarbonInterface|null $expirationDate
     * @return CarbonInterface|null
     */
    private function getRenewedExpiration(?CarbonInterface $expirationDate = null)
    {
        if (! empty($expirationDate)) {
            return $expirationDate;
        }

        if (empty($this->plan->periodicity)) {
            return null;
        }

        if ($this->isOverdue) {
            return $this->plan->calculateNextRecurrenceEnd();
        }

        return $this->plan->calculateNextRecurrenceEnd($this->expired_at);
    }
}
