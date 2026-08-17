<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\FlashcardEngineSM2\Models\Deck;
use App\Domains\FlashcardEngineSM2\Models\Flashcard;
use App\Domains\FlashcardEngineSM2\Services\Sm2Algorithm;
use Laravel\Sanctum\Sanctum;

class FlashcardTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_sm2_algorithm_schedules_intervals(): void
    {
        $sm2 = new Sm2Algorithm();

        $r1 = $sm2->schedule(5, 2.5, 0, 0);
        $this->assertEquals(1, $r1['interval_days']);
        $this->assertEquals(1, $r1['repetitions']);
        $this->assertEquals(2.6, $r1['ease_factor']);

        $r2 = $sm2->schedule(5, $r1['ease_factor'], $r1['interval_days'], $r1['repetitions']);
        $this->assertEquals(6, $r2['interval_days']);

        $r3 = $sm2->schedule(5, $r2['ease_factor'], $r2['interval_days'], $r2['repetitions']);
        $this->assertGreaterThan(6, $r3['interval_days']);

        $fail = $sm2->schedule(1, $r2['ease_factor'], $r2['interval_days'], $r2['repetitions']);
        $this->assertEquals(1, $fail['interval_days']);
        $this->assertEquals(0, $fail['repetitions']);
    }

    public function test_deck_and_flashcard_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $deck = $this->postJson('/api/v1/decks', ['title' => 'Spanish']);
        $deck->assertCreated();
        $deckId = $deck->json('data.id');

        $card = $this->postJson("/api/v1/decks/{$deckId}/flashcards", [
            'question' => 'hola',
            'answer' => 'hello',
        ]);
        $card->assertCreated();
        $cardId = $card->json('data.id');
        $this->assertEquals('hola', $card->json('data.question'));
        $this->assertTrue($card->json('data.is_due'));

        $this->deleteJson("/api/v1/flashcards/{$cardId}")->assertNoContent();
        $this->assertDatabaseMissing('flashcards', ['id' => $cardId]);
    }

    public function test_review_advances_scheduler(): void
    {
        $deck = Deck::create(['user_id' => $this->owner->id, 'title' => 'History']);
        $card = Flashcard::create(['deck_id' => $deck->id, 'user_id' => $this->owner->id, 'question' => 'Q', 'answer' => 'A']);

        Sanctum::actingAs($this->owner);
        $res = $this->postJson("/api/v1/flashcards/{$card->id}/review", ['quality' => 5]);

        $res->assertOk();
        $this->assertEquals(1, $res->json('data.next.interval_days'));
        $this->assertEquals(1, $res->json('data.next.repetitions'));
        $this->assertEquals(1, $res->json('data.flashcard.interval_days'));
        $this->assertNotNull($res->json('data.flashcard.due_at'));
        $this->assertFalse($res->json('data.flashcard.is_due'));
    }

    public function test_review_failure_resets_repetitions(): void
    {
        $deck = Deck::create(['user_id' => $this->owner->id, 'title' => 'Math']);
        $card = Flashcard::create([
            'deck_id' => $deck->id,
            'user_id' => $this->owner->id,
            'question' => 'Q',
            'answer' => 'A',
            'ease_factor' => 2.5,
            'interval_days' => 10,
            'repetitions' => 5,
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->postJson("/api/v1/flashcards/{$card->id}/review", ['quality' => 1]);

        $res->assertOk();
        $this->assertEquals(1, $res->json('data.next.interval_days'));
        $this->assertEquals(0, $res->json('data.next.repetitions'));
    }

    public function test_due_and_stats(): void
    {
        $deck = Deck::create(['user_id' => $this->owner->id, 'title' => 'Deck']);
        Flashcard::create(['deck_id' => $deck->id, 'user_id' => $this->owner->id, 'question' => 'Due now', 'answer' => 'A']);
        $later = Flashcard::create(['deck_id' => $deck->id, 'user_id' => $this->owner->id, 'question' => 'Later', 'answer' => 'B', 'due_at' => now()->addDays(5)]);

        Sanctum::actingAs($this->owner);
        $due = $this->getJson('/api/v1/flashcards/due');
        $due->assertOk();
        $this->assertCount(1, $due->json('data'));
        $this->assertEquals('Due now', $due->json('data.0.question'));

        $this->postJson("/api/v1/flashcards/{$later->id}/review", ['quality' => 4])->assertOk();

        $stats = $this->getJson('/api/v1/flashcards/stats');
        $stats->assertOk();
        $this->assertEquals(2, $stats->json('data.total_cards'));
        $this->assertEquals(1, $stats->json('data.due_now'));
        $this->assertEquals(1, $stats->json('data.total_reviews'));
        $this->assertEquals(100, $stats->json('data.success_rate'));
    }

    public function test_guest_cannot_access_flashcards(): void
    {
        $this->getJson('/api/v1/flashcards')->assertUnauthorized();
        $this->getJson('/api/v1/decks')->assertUnauthorized();
    }
}