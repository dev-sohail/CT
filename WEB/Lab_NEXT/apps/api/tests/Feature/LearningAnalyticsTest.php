<?php

namespace Tests\Feature;

use App\Domains\BookmarkAndReadLaterArchive\Models\Bookmark;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\FlashcardEngineSM2\Models\Deck;
use App\Domains\FlashcardEngineSM2\Models\Flashcard;
use App\Domains\FlashcardEngineSM2\Models\FlashcardReview;
use App\Domains\StudyAndCourseTracker\Models\Course;
use Laravel\Sanctum\Sanctum;

class LearningAnalyticsTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_overview_aggregates_all_sources(): void
    {
        Course::create(['user_id' => $this->owner->id, 'title' => 'A', 'status' => 'in_progress', 'hours_spent' => 3]);
        $deck = Deck::create(['user_id' => $this->owner->id, 'title' => 'Deck']);
        Flashcard::create(['deck_id' => $deck->id, 'user_id' => $this->owner->id, 'question' => 'Q', 'answer' => 'A']);
        Bookmark::create(['user_id' => $this->owner->id, 'url' => 'https://x.dev', 'title' => 'B', 'status' => 'unread']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/learning/overview');

        $res->assertOk();
        $this->assertEquals(1, $res->json('data.courses.in_progress'));
        $this->assertEquals(3, $res->json('data.courses.total_hours'));
        $this->assertEquals(1, $res->json('data.flashcards.total'));
        $this->assertEquals(1, $res->json('data.flashcards.due'));
        $this->assertEquals(1, $res->json('data.reading.unread'));
    }

    public function test_streaks_detects_current_and_longest(): void
    {
        $deck = Deck::create(['user_id' => $this->owner->id, 'title' => 'Deck']);
        $card = Flashcard::create(['deck_id' => $deck->id, 'user_id' => $this->owner->id, 'question' => 'Q', 'answer' => 'A']);

        FlashcardReview::create(['flashcard_id' => $card->id, 'quality' => 5, 'created_at' => now()]);
        FlashcardReview::create(['flashcard_id' => $card->id, 'quality' => 5, 'created_at' => now()->subDay()]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/learning/streaks');

        $res->assertOk();
        $this->assertGreaterThanOrEqual(2, $res->json('data.current_streak'));
        $this->assertGreaterThanOrEqual(2, $res->json('data.longest_streak'));
    }

    public function test_retention_curve_returns_success_rates(): void
    {
        $deck = Deck::create(['user_id' => $this->owner->id, 'title' => 'Deck']);
        $card = Flashcard::create(['deck_id' => $deck->id, 'user_id' => $this->owner->id, 'question' => 'Q', 'answer' => 'A']);
        FlashcardReview::create(['flashcard_id' => $card->id, 'quality' => 5, 'created_at' => now()]);
        FlashcardReview::create(['flashcard_id' => $card->id, 'quality' => 2, 'created_at' => now()]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/learning/retention?days=7');

        $res->assertOk();
        $this->assertCount(7, $res->json('data'));
        $today = collect($res->json('data'))->last();
        $this->assertEquals(2, $today['reviews']);
        $this->assertEquals(50, $today['success_rate']);
    }

    public function test_guest_cannot_access_learning_analytics(): void
    {
        $this->getJson('/api/v1/learning/overview')->assertUnauthorized();
    }
}