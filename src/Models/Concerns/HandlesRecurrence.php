<?php

namespace Squarhe\Subscription\Models\Concerns;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Squarhe\Subscription\Enums\PeriodicityType;

/**
 * Provides recurrence date calculations for plans and features.
 */
trait HandlesRecurrence
{
    public function calculateNextRecurrenceEnd(CarbonInterface|string|null $start = null): CarbonInterface
    {
        if (empty($start)) {
            $start = now();
        }

        if (is_string($start)) {
            $start = Carbon::parse($start);
        }

        $recurrences = max(
            PeriodicityType::getDateDifference(from: $start, to: now(), unit: $this->periodicity_type),
            0,
        );

        $expirationDate = $start->copy()->add($this->periodicity_type, $this->periodicity + $recurrences);

        return $expirationDate;
    }
}
