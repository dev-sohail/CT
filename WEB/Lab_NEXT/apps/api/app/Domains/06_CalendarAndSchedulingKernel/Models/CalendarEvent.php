<?php

namespace App\Domains\CalendarAndSchedulingKernel\Models;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use SoftDeletes;
    protected $table = 'calendar_events';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'is_all_day',
        'timezone',
        'status',
        'recurrence_rule',
        'recurrence_ends_at',
        'sourceable_type',
        'sourceable_id',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'recurrence_ends_at' => 'datetime',
        'is_all_day' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('starts_at', '>=', $from);
        }
        if ($to) {
            $query->where('starts_at', '<=', $to);
        }
        return $query;
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled');
    }

    // ------------------------------------------------------------------
    // Recurrence
    // ------------------------------------------------------------------

    public function isRecurring(): bool
    {
        return !empty($this->recurrence_rule);
    }

    /**
     * Expand a recurring event into virtual occurrences within a date range.
     *
     * Returns an array of arrays, each shaped like a CalendarEvent but
     * without an id (they are virtual — not persisted).
     *
     * @return list<array<string, mixed>>
     */
    public function expandOccurrences(string $fromDate, string $toDate, int $maxOccurrences = 100): array
    {
        if (!$this->isRecurring()) {
            return [
                [
                    'id' => $this->id,
                    'title' => $this->title,
                    'description' => $this->description,
                    'location' => $this->location,
                    'starts_at' => $this->starts_at?->toIso8601String(),
                    'ends_at' => $this->ends_at?->toIso8601String(),
                    'is_all_day' => $this->is_all_day,
                    'timezone' => $this->timezone,
                    'status' => $this->status,
                    'recurrence_rule' => $this->recurrence_rule,
                ],
            ];
        }

        $rule = $this->parseRRule();
        if ($rule === null) {
            return [];
        }

        $from = \Carbon\Carbon::parse($fromDate);
        $to = \Carbon\Carbon::parse($toDate);
        $baseStart = $this->starts_at?->copy() ?? now();

        switch ($rule['freq']) {
            case 'daily':
                return $this->expandDaily($baseStart, $from, $to, $rule, $maxOccurrences);
            case 'weekly':
                return $this->expandWeekly($baseStart, $from, $to, $rule, $maxOccurrences);
            case 'monthly':
                return $this->expandMonthly($baseStart, $from, $to, $rule, $maxOccurrences);
            case 'yearly':
                return $this->expandYearly($baseStart, $from, $to, $rule, $maxOccurrences);
            default:
                return [];
        }
    }

    /**
     * Parse a simplified RRULE string (FREQ, INTERVAL, COUNT, UNTIL, BYDAY).
     *
     * @return array<string, mixed>|null
     */
    protected function parseRRule(): ?array
    {
        $rule = ['freq' => 'daily', 'interval' => 1, 'count' => null, 'until' => null, 'byday' => []];
        $parts = explode(';', strtoupper($this->recurrence_rule));

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;
            [$key, $val] = array_pad(explode('=', $part, 2), 2, '');
            $key = trim($key); $val = trim($val);

            switch ($key) {
                case 'FREQ':
                    $rule['freq'] = strtolower($val);
                    break;
                case 'INTERVAL':
                    $rule['interval'] = max(1, (int) $val);
                    break;
                case 'COUNT':
                    $rule['count'] = (int) $val;
                    break;
                case 'UNTIL':
                    $rule['until'] = \Carbon\Carbon::parse($val);
                    break;
                case 'BYDAY':
                    $days = explode(',', $val);
                    $map = ['MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 0];
                    foreach ($days as $d) {
                        $d = trim($d);
                        if (isset($map[$d])) $rule['byday'][] = $map[$d];
                    }
                    break;
            }
        }

        return $rule;
    }

    protected function makeOccurrence(\Carbon\Carbon $start): array
    {
        $end = $this->ends_at
            ? $start->copy()->addSeconds($this->starts_at->diffInSeconds($this->ends_at))
            : null;

        return [
            'id' => null, // virtual occurrence
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $end?->toIso8601String(),
            'is_all_day' => $this->is_all_day,
            'timezone' => $this->timezone,
            'status' => $this->status,
            'recurrence_rule' => null, // expanded occurrence
        ];
    }

    protected function shouldStop(\Carbon\Carbon $date, array $rule, int $count): bool
    {
        if ($rule['count'] !== null && $count > $rule['count']) return true;
        if ($rule['until'] !== null && $date->gt($rule['until'])) return true;
        if ($this->recurrence_ends_at !== null && $date->gt($this->recurrence_ends_at)) return true;
        return false;
    }

    protected function expandDaily(\Carbon\Carbon $base, \Carbon\Carbon $from, \Carbon\Carbon $to, array $rule, int $max): array
    {
        $occurrences = [];
        $current = $base->copy();

        while ($current->lte($to) && count($occurrences) < $max) {
            $count = count($occurrences) + 1;
            if ($this->shouldStop($current, $rule, $count)) break;

            $occurrences[] = $this->makeOccurrence($current);
            $current->addDays($rule['interval']);
        }

        return $occurrences;
    }

    protected function expandWeekly(\Carbon\Carbon $base, \Carbon\Carbon $from, \Carbon\Carbon $to, array $rule, int $max): array
    {
        $occurrences = [];
        $weekdays = $rule['byday'] ?: [(int) $base->dayOfWeek];

        $weekStart = $base->copy()->startOfWeek();

        while ($weekStart->lte($to) && count($occurrences) < $max) {
            foreach ($weekdays as $day) {
                $occurrenceDate = $weekStart->copy()->addDays($day);

                if ($occurrenceDate->lt($base)) continue;
                if ($occurrenceDate->gt($to)) continue;

                $count = count($occurrences) + 1;
                if ($this->shouldStop($occurrenceDate, $rule, $count)) break 2;

                $occurrences[] = $this->makeOccurrence($occurrenceDate);
            }
            $weekStart->addWeeks($rule['interval']);
        }

        return $occurrences;
    }

    protected function expandMonthly(\Carbon\Carbon $base, \Carbon\Carbon $from, \Carbon\Carbon $to, array $rule, int $max): array
    {
        $occurrences = [];
        $current = $base->copy();

        while ($current->lte($to) && count($occurrences) < $max) {
            $count = count($occurrences) + 1;
            if ($this->shouldStop($current, $rule, $count)) break;

            $occurrences[] = $this->makeOccurrence($current);
            $current->addMonths($rule['interval']);
        }

        return $occurrences;
    }

    protected function expandYearly(\Carbon\Carbon $base, \Carbon\Carbon $from, \Carbon\Carbon $to, array $rule, int $max): array
    {
        $occurrences = [];
        $current = $base->copy();

        while ($current->lte($to) && count($occurrences) < $max) {
            $count = count($occurrences) + 1;
            if ($this->shouldStop($current, $rule, $count)) break;

            $occurrences[] = $this->makeOccurrence($current);
            $current->addYears($rule['interval']);
        }

        return $occurrences;
    }
}