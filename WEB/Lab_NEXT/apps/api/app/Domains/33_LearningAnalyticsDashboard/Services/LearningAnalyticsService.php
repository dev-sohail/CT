<?php

namespace App\Domains\LearningAnalyticsDashboard\Services;

use App\Domains\BookmarkAndReadLaterArchive\Models\Bookmark;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\FlashcardEngineSM2\Models\Flashcard;
use App\Domains\FlashcardEngineSM2\Models\FlashcardReview;
use App\Domains\StudyAndCourseTracker\Models\Course;
use Carbon\Carbon;

class LearningAnalyticsService
{
    public function overview(User $user): array
    {
        $courses = Course::forUser($user->id)->get();
        $cards = Flashcard::forUser($user->id)->get();
        $bookmarks = Bookmark::forUser($user->id)->get();

        return [
            'courses' => [
                'total' => $courses->count(),
                'in_progress' => $courses->where('status', 'in_progress')->count(),
                'completed' => $courses->where('status', 'completed')->count(),
                'total_hours' => round($courses->sum('hours_spent'), 2),
            ],
            'flashcards' => [
                'total' => $cards->count(),
                'due' => $cards->filter(fn ($c) => $c->due_at === null || $c->due_at->lte(now()))->count(),
                'avg_ease_factor' => $cards->isEmpty() ? null : round($cards->avg('ease_factor'), 2),
            ],
            'reading' => [
                'total' => $bookmarks->count(),
                'unread' => $bookmarks->where('status', 'unread')->count(),
            ],
        ];
    }

    public function studyStreak(User $user): array
    {
        // A study day = any course hours logged or flashcard review on that day.
        $days = $this->studyDays($user);

        $current = 0;
        $best = 0;
        $cursor = now()->startOfDay();

        // Longest streak.
        $dates = $days->keys()->map(fn ($d) => Carbon::parse($d)->startOfDay())->sort()->values();
        foreach ($dates as $date) {
            if ($date->eq($cursor)) {
                $current++;
                $best = max($best, $current);
                $cursor->addDay();
            } else {
                $current = 1;
                $best = max($best, $current);
                $cursor = $date->copy()->addDay();
            }
        }

        // Current streak counting backwards from today.
        $currentStreak = 0;
        $day = now()->startOfDay();
        while ($days->has($day->toDateString())) {
            $currentStreak++;
            $day->subDay();
        }

        return [
            'current_streak' => $currentStreak,
            'longest_streak' => $best,
            'study_days_30d' => $days->filter(fn ($v, $k) => Carbon::parse($k)->gte(now()->subDays(30)->startOfDay()))->count(),
            'total_study_days' => $days->count(),
        ];
    }

    public function retentionCurve(User $user, int $days = 14): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $reviewIds = Flashcard::forUser($user->id)->pluck('id');
        $reviews = FlashcardReview::whereIn('flashcard_id', $reviewIds)
            ->where('created_at', '>=', $from)
            ->get()
            ->groupBy(fn ($r) => $r->created_at->toDateString());

        $points = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $dayReviews = $reviews->get($day, collect());
            $points[] = [
                'date' => $day,
                'reviews' => $dayReviews->count(),
                'success_rate' => $dayReviews->isEmpty()
                    ? null
                    : round(($dayReviews->where('quality', '>=', 3)->count() / $dayReviews->count()) * 100, 1),
            ];
        }

        return $points;
    }

    private function studyDays(User $user): \Illuminate\Support\Collection
    {
        $courseDays = collect();
        foreach (Course::forUser($user->id)->whereNotNull('updated_at')->get() as $course) {
            $courseDays->put($course->updated_at->toDateString(), true);
        }

        $reviewIds = Flashcard::forUser($user->id)->pluck('id');
        foreach (FlashcardReview::whereIn('flashcard_id', $reviewIds)->get() as $review) {
            $courseDays->put($review->created_at->toDateString(), true);
        }

        return $courseDays;
    }
}