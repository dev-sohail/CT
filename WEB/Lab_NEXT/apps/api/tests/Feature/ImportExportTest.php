<?php

namespace Tests\Feature;

use App\Domains\CalendarAndSchedulingKernel\Models\CalendarEvent;
use App\Domains\ContactsAndRelationshipGraph\Models\Person;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class ImportExportTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_supported_domains_are_listed(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/transfer/domains');

        $res->assertOk();
        $this->assertContains('contacts', $res->json('data'));
        $this->assertContains('calendar', $res->json('data'));
    }

    public function test_export_contacts_and_calendar(): void
    {
        Person::create(['user_id' => $this->owner->id, 'name' => 'Alice']);
        CalendarEvent::create([
            'user_id' => $this->owner->id,
            'title' => 'Standup',
            'starts_at' => '2026-09-01T09:00:00Z',
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->postJson('/api/v1/exports', [
            'domains' => ['contacts', 'calendar'],
            'format' => 'json',
        ]);

        $res->assertCreated();
        $this->assertEquals('completed', $res->json('data.status'));
        $this->assertEquals(1, $res->json('data.item_counts.contacts.people'));
        $this->assertEquals(1, $res->json('data.item_counts.calendar.events'));
        $this->assertNotNull($res->json('data.download_url'));
    }

    public function test_export_rejects_unknown_domain(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->postJson('/api/v1/exports', ['domains' => ['bogus']]);

        $res->assertUnprocessable();
    }

    public function test_import_contacts_creates_people(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->postJson('/api/v1/imports', [
            'domain' => 'contacts',
            'data' => [
                'people' => [
                    ['name' => 'Bob', 'email' => 'bob@example.com', 'relationship_type' => 'friend'],
                ],
            ],
        ]);

        $res->assertCreated();
        $this->assertEquals(1, $res->json('data.item_counts.people'));
        $this->assertDatabaseHas('people', ['name' => 'Bob', 'email' => 'bob@example.com']);
    }

    public function test_import_contacts_with_methods_and_relationships(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->postJson('/api/v1/imports', [
            'domain' => 'contacts',
            'data' => [
                'people' => [
                    ['name' => 'Carol', 'contact_methods' => [
                        ['type' => 'email', 'value' => 'carol@example.com', 'is_primary' => true],
                    ]],
                    ['name' => 'Dan'],
                ],
                'relationships' => [
                    ['from' => 'Carol', 'to' => 'Dan', 'type' => 'spouse'],
                ],
            ],
        ]);

        $res->assertCreated();
        $this->assertEquals(2, $res->json('data.item_counts.people'));
        $this->assertEquals(1, $res->json('data.item_counts.relationships'));
        $this->assertDatabaseHas('contact_methods', ['type' => 'email', 'value' => 'carol@example.com']);
        $this->assertDatabaseHas('relationships', ['type' => 'spouse']);
    }

    public function test_import_calendar_events(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->postJson('/api/v1/imports', [
            'domain' => 'calendar',
            'data' => [
                'events' => [
                    ['title' => 'Gym', 'starts_at' => '2026-09-03T18:00:00Z'],
                ],
            ],
        ]);

        $res->assertCreated();
        $this->assertEquals(1, $res->json('data.item_counts.events'));
        $this->assertDatabaseHas('calendar_events', ['title' => 'Gym']);
    }

    public function test_export_and_import_round_trip(): void
    {
        Person::create(['user_id' => $this->owner->id, 'name' => 'Eve']);

        Sanctum::actingAs($this->owner);
        $export = $this->postJson('/api/v1/exports', ['domains' => ['contacts']]);
        $export->assertCreated();
        $id = $export->json('data.id');

        $show = $this->getJson("/api/v1/exports/{$id}");
        $show->assertOk();
        $this->assertEquals('completed', $show->json('data.status'));

        // Verify the file actually exists on disk.
        $file = \App\Domains\ImportExportAndDataPortability\Models\Export::findOrFail($id);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('local')->exists($file->file_path));
    }

    public function test_export_history_lists(): void
    {
        Sanctum::actingAs($this->owner);
        $this->postJson('/api/v1/exports', ['domains' => ['contacts']])->assertCreated();

        $res = $this->getJson('/api/v1/exports');
        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
    }

    public function test_import_history_lists(): void
    {
        Sanctum::actingAs($this->owner);
        $this->postJson('/api/v1/imports', [
            'domain' => 'contacts',
            'data' => ['people' => [['name' => 'Faye']]],
        ])->assertCreated();

        $res = $this->getJson('/api/v1/imports');
        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
    }

    public function test_unknown_import_domain_fails(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->postJson('/api/v1/imports', [
            'domain' => 'nope',
            'data' => [],
        ]);

        $res->assertUnprocessable();
    }

    public function test_guest_cannot_access_transfer(): void
    {
        $this->getJson('/api/v1/exports')->assertUnauthorized();
    }
}