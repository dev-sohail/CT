<?php

namespace App\Domains\FlashcardEngineSM2\Services;

/**
 * The classic SuperMemo-2 (SM-2) spaced-repetition algorithm.
 * Given a recall quality (0-5) and current scheduler state, computes
 * the next ease factor, interval, and repetitions.
 */
class Sm2Algorithm
{
    /**
     * @return array{ease_factor: float, interval_days: int, repetitions: int}
     */
    public function schedule(int $quality, float $easeFactor, int $intervalDays, int $repetitions): array
    {
        $quality = max(0, min(5, $quality));

        if ($quality < 3) {
            // Repetition failed: reset to relearning.
            return [
                'ease_factor' => $easeFactor,
                'interval_days' => 1,
                'repetitions' => 0,
            ];
        }

        $repetitions++;

        if ($repetitions === 1) {
            $interval = 1;
        } elseif ($repetitions === 2) {
            $interval = 6;
        } else {
            $interval = (int) round($intervalDays * $easeFactor);
        }

        $easeFactor = $easeFactor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));
        $easeFactor = max(1.3, round($easeFactor, 2));

        return [
            'ease_factor' => $easeFactor,
            'interval_days' => max(1, $interval),
            'repetitions' => $repetitions,
        ];
    }
}