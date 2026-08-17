<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\JournalAndStructuredReviewSystem\Models\JournalEntry;
use Laravel\Sanctum\Sanctum;

class JournalEntryTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_journal_crud_and_structured_fields(): void
    {
        Sanctum::actingAs($this->owner);
        $created = $this->postJson('/api/v1/journal', [
            'entry_date' => now()->toDateString(), 'title' => 'Daily review', 'body' => 'A good day',
            'mood' => 8, 'energy' => 7, 'gratitude' => ['family'], 'wins' => ['shipped'], 'tags' => ['review'],
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals(8, $created->json('data.mood'));
        $this->assertEquals(['family'], $created->json('data.gratitude'));

        $this->putJson("/api/v1/journal/{$id}", ['mood' => 9])->assertOk();
        $this->deleteJson("/api/v1/journal/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('journal_entries', ['id' => $id]);
    }

    public function test_calendar_and_filters(): void
    {
        JournalEntry::create(['user_id' => $this->owner->id, 'entry_date' => now()->toDateString(), 'body' => 'One', 'tags' => ['work'], 'mood' => 6]);
        JournalEntry::create(['user_id' => $this->owner->id, 'entry_date' => now()->subMonth()->toDateString(), 'body' => 'Two', 'tags' => ['home'], 'mood' => 4]);
        Sanctum::actingAs($this->owner);

        $this->assertCount(1, $this->getJson('/api/v1/journal?tag=work')->json('data'));
        $calendar = $this->getJson('/api/v1/journal/calendar?from='.now()->startOfMonth()->toDateString().'&to='.now()->endOfMonth()->toDateString());
        $calendar->assertOk();
        $this->assertCount(1, $calendar->json('data'));
    }

    public function test_stats_and_guest_access(): void
    {
        JournalEntry::create(['user_id' => $this->owner->id, 'entry_date' => now()->toDateString(), 'body' => 'A', 'mood' => 8, 'energy' => 6]);
        JournalEntry::create(['user_id' => $this->owner->id, 'entry_date' => now()->subDay()->toDateString(), 'body' => 'B', 'mood' => 6, 'energy' => 8]);
        $this->getJson('/api/v1/journal')->assertUnauthorized();
        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/journal/stats');
        $res->assertOk();
        $this->assertEquals(2, $res->json('data.total'));
        $this->assertEquals(7, $res->json('data.average_mood'));
    }
}
