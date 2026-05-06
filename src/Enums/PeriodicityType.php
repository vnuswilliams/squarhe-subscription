<?php

namespace Squarhe\Subscription\Enums;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Periodicity helper.
 *
 * Centralizes supported recurrence units (day, week, month, year)
 * and provides a signed date-difference calculation between two dates.
 */
class PeriodicityType
{
    public const Year = 'Year';

    public const Month = 'Month';

    public const Week = 'Week';

    public const Day = 'Day';

    /**
     * Calculates the signed difference between two dates using a Carbon unit.
     *
     * @param CarbonInterface $from
     * @param CarbonInterface $to
     * @param string $unit
     * @return int
     */
    public static function getDateDifference(CarbonInterface $from, CarbonInterface $to, string $unit): int
    {
        if ($from->isAfter($to)) {
            $delta = -1;
        } else {
            $delta = 1;
        }

        $unitInPlural = Str::plural($unit);

        $differenceMethodName = 'diffIn' . $unitInPlural;
        $difference = abs($from->{$differenceMethodName}($to));

        return $difference * $delta;
    }
}
