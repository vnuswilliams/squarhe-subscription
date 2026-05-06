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

class Subscription extends Model
{
    use ExpiresAndHasGraceDays;
    use HasFactory;
    use SoftDeletes;
    use Starts;
    use Suppresses;

    protected $dates = [
        'canceled_at',
    ];

    protected $fillable = [
        'canceled_at',
        'expired_at',
        'grace_days_ended_at',
        'started_at',
        'suppressed_at',
        'was_switched',
    ];

    public function plan()
    {
        return $this->belongsTo(config('soulbscription.models.plan'));
    }

    public function renewals()
    {
        return $this->hasMany(config('soulbscription.models.subscription_renewal'));
    }

    public function subscriber()
    {
        return $this->morphTo('subscriber');
    }

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

    public function scopeCanceled(Builder $query)
    {
        return $query->whereNotNull('canceled_at');
    }

    public function scopeNotCanceled(Builder $query)
    {
        return $query->whereNull('canceled_at');
    }

    public function markAsSwitched(): self
    {
        return $this->fill([
            'was_switched' => true,
        ]);
    }

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

    public function cancel(?CarbonInterface $cancelDate = null): self
    {
        $cancelDate = $cancelDate ?: now();

        $this->fill(['canceled_at' => $cancelDate])
            ->save();

        event(new SubscriptionCanceled($this));

        return $this;
    }

    public function suppress(?CarbonInterface $suppressation = null)
    {
        $suppressationDate = $suppressation ?: now();

        $this->fill(['suppressed_at' => $suppressationDate])
            ->save();

        event(new SubscriptionSuppressed($this));

        return $this;
    }

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
