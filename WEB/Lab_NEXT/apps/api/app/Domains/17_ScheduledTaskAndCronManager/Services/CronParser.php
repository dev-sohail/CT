<?php

namespace App\Domains\ScheduledTaskAndCronManager\Services;

use Carbon\Carbon;

/**
 * A small, deterministic parser for standard 5-field cron expressions
 * (minute hour day-of-month month day-of-week).
 */
class CronParser
{
    private const MONTHS = [
        'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'JUN' => 6,
        'JUL' => 7, 'AUG' => 8, 'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12,
    ];

    private const DOW = [
        'SUN' => 0, 'MON' => 1, 'TUE' => 2, 'WED' => 3, 'THU' => 4, 'FRI' => 5, 'SAT' => 6,
    ];

    public function isExpressionValid(string $expression): bool
    {
        $fields = explode(' ', trim($expression));
        if (count($fields) !== 5) {
            return false;
        }
        [$min, $hour, $dom, $month, $dow] = $fields;
        if ($this->parseField($min, 0, 59) === null) return false;
        if ($this->parseField($hour, 0, 23) === null) return false;
        if ($this->parseField($dom, 1, 31) === null) return false;
        if ($this->parseField($month, 1, 12, self::MONTHS) === null) return false;
        if ($this->parseField($dow, 0, 7, self::DOW) === null) return false;
        return true;
    }

    public function nextRun(string $expression, Carbon $after): ?Carbon
    {
        $fields = explode(' ', trim($expression));
        if (count($fields) !== 5) {
            return null;
        }
        [$min, $hour, $dom, $month, $dow] = $fields;

        $minutes = $this->parseField($min, 0, 59);
        $hours = $this->parseField($hour, 0, 23);
        $days = $this->parseField($dom, 1, 31);
        $months = $this->parseField($month, 1, 12, self::MONTHS);
        $weekdays = $this->parseField($dow, 0, 7, self::DOW);
        if (!$minutes || !$hours || !$days || !$months || !$weekdays) {
            return null;
        }
        // Normalize day-of-week: cron treats 7 as Sunday (=0).
        $weekdays = array_map(fn ($d) => $d === 7 ? 0 : $d, $weekdays);

        $cursor = $after->copy()->startOfMinute()->addMinute();
        $limit = $cursor->copy()->addYears(4);

        while ($cursor->lte($limit)) {
            if (in_array($cursor->minute, $minutes, true)
                && in_array($cursor->hour, $hours, true)
                && in_array($cursor->day, $days, true)
                && in_array($cursor->month, $months, true)
                && in_array($cursor->dayOfWeek, $weekdays, true)) {
                return $cursor;
            }
            $cursor->addMinute();
        }

        return null;
    }

    private function parseField(string $field, int $min, int $max, array $names = []): ?array
    {
        $field = strtoupper(trim($field));
        if ($field === '*') {
            return range($min, $max);
        }

        $values = [];
        foreach (explode(',', $field) as $part) {
            $part = trim($part);
            if ($part === '') {
                return null;
            }

            $step = 1;
            if (str_contains($part, '/')) {
                [$range, $stepStr] = explode('/', $part, 2);
                $step = (int) $stepStr;
                if ($step < 1) {
                    return null;
                }
                $part = $range;
            }

            if ($part === '*') {
                $lo = $min;
                $hi = $max;
            } elseif (str_contains($part, '-')) {
                [$loStr, $hiStr] = explode('-', $part, 2);
                $lo = $this->mapName($loStr, $names);
                $hi = $this->mapName($hiStr, $names);
                if ($lo === null || $hi === null) {
                    return null;
                }
            } else {
                $single = $this->mapName($part, $names);
                if ($single === null) {
                    return null;
                }
                $lo = $single;
                $hi = $single;
            }

            if ($lo < $min || $hi > $max || $lo > $hi) {
                return null;
            }

            for ($v = $lo; $v <= $hi; $v += $step) {
                $values[] = $v;
            }
        }

        return array_values(array_unique($values));
    }

    private function mapName(string $token, array $names): ?int
    {
        $token = trim($token);
        if (is_numeric($token)) {
            return (int) $token;
        }
        return $names[$token] ?? null;
    }
}