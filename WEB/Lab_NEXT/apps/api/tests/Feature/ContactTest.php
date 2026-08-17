<?php

namespace Tests\Feature;

use App\Domains\ContactsAndRelationshipGraph\Models\Person;
use App\Domains\ContactsAndRelationshipGraph\Models\Relationship;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class ContactTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_index_returns_owner_contacts(): void
    {
        Person::create(['user_id' => $this->owner->id, 'name' => 'Alice']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/contacts');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Alice', $res->json('data.0.name'));
    }

    public function test_search_filters_by_term(): void
    {
        Person::create(['user_id' => $this->owner->id, 'name' => 'Alice Smith']);
        Person::create(['user_id' => $this->owner->id, 'name' => 'Bob Jones']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/contacts?q=alic');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Alice Smith', $res->json('data.0.name'));
    }

    public function test_of_type_filter(): void
    {
        Person::create(['user_id' => $this->owner->id, 'name' => 'Mom', 'relationship_type' => 'family']);
        Person::create(['user_id' => $this->owner->id, 'name' => 'Carol', 'relationship_type' => 'friend']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/contacts?type=family');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Mom', $res->json('data.0.name'));
    }

    public function test_store_creates_person_with_contact_methods(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->postJson('/api/v1/contacts', [
            'name' => 'Dave',
            'email' => 'dave@example.com',
            'relationship_type' => 'colleague',
            'contact_methods' => [
                ['type' => 'email', 'value' => 'dave@example.com', 'is_primary' => true],
                ['type' => 'phone', 'value' => '+15551234', 'label' => 'mobile'],
            ],
        ]);

        $res->assertCreated();
        $this->assertEquals('Dave', $res->json('data.name'));
        $this->assertCount(2, $res->json('data.contact_methods'));

        $this->assertDatabaseHas('contact_methods', ['type' => 'phone', 'value' => '+15551234']);
    }

    public function test_show_returns_person(): void
    {
        $person = Person::create(['user_id' => $this->owner->id, 'name' => 'Eve']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson("/api/v1/contacts/{$person->id}");

        $res->assertOk();
        $this->assertEquals('Eve', $res->json('data.name'));
    }

    public function test_update_modifies_person(): void
    {
        $person = Person::create(['user_id' => $this->owner->id, 'name' => 'Frank']);

        Sanctum::actingAs($this->owner);
        $res = $this->putJson("/api/v1/contacts/{$person->id}", ['name' => 'Franklin']);

        $res->assertOk();
        $this->assertEquals('Franklin', $res->json('data.name'));
        $this->assertDatabaseHas('people', ['name' => 'Franklin']);
    }

    public function test_delete_soft_deletes_person(): void
    {
        $person = Person::create(['user_id' => $this->owner->id, 'name' => 'Grace']);

        Sanctum::actingAs($this->owner);
        $res = $this->deleteJson("/api/v1/contacts/{$person->id}");

        $res->assertNoContent();
        $this->assertSoftDeleted('people', ['id' => $person->id]);
    }

    public function test_cannot_access_another_users_contact(): void
    {
        $other = User::factory()->create();
        $person = Person::create(['user_id' => $other->id, 'name' => 'Private']);

        Sanctum::actingAs($this->owner);
        $this->getJson("/api/v1/contacts/{$person->id}")->assertNotFound();
        $this->deleteJson("/api/v1/contacts/{$person->id}")->assertNotFound();
    }

    public function test_add_and_list_relationship(): void
    {
        $alice = Person::create(['user_id' => $this->owner->id, 'name' => 'Alice']);
        $bob = Person::create(['user_id' => $this->owner->id, 'name' => 'Bob']);

        Sanctum::actingAs($this->owner);
        $add = $this->postJson("/api/v1/contacts/{$alice->id}/relationships", [
            'related_person_id' => $bob->id,
            'type' => 'spouse',
        ]);
        $add->assertCreated();
        $this->assertEquals('Bob', $add->json('data.related_person.name'));

        $list = $this->getJson("/api/v1/contacts/{$alice->id}/relationships");
        $list->assertOk();
        $this->assertCount(1, $list->json('data'));
        $this->assertEquals('spouse', $list->json('data.0.type'));
    }

    public function test_cannot_relate_to_self(): void
    {
        $alice = Person::create(['user_id' => $this->owner->id, 'name' => 'Alice']);

        Sanctum::actingAs($this->owner);
        $res = $this->postJson("/api/v1/contacts/{$alice->id}/relationships", [
            'related_person_id' => $alice->id,
            'type' => 'self',
        ]);

        $res->assertUnprocessable();
    }

    public function test_remove_relationship(): void
    {
        $alice = Person::create(['user_id' => $this->owner->id, 'name' => 'Alice']);
        $bob = Person::create(['user_id' => $this->owner->id, 'name' => 'Bob']);
        $rel = Relationship::create([
            'user_id' => $this->owner->id,
            'person_id' => $alice->id,
            'related_person_id' => $bob->id,
            'type' => 'friend',
        ]);

        Sanctum::actingAs($this->owner);
        $this->deleteJson("/api/v1/contacts/{$alice->id}/relationships/{$rel->id}")->assertNoContent();
        $this->assertDatabaseMissing('relationships', ['id' => $rel->id]);
    }

    public function test_birthday_soon_returns_upcoming(): void
    {
        Person::create([
            'user_id' => $this->owner->id,
            'name' => 'Soon',
            'birthday' => now()->addDays(5)->toDateString(),
        ]);
        Person::create([
            'user_id' => $this->owner->id,
            'name' => 'Far',
            'birthday' => now()->addDays(200)->toDateString(),
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/contacts/birthday-soon');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Soon', $res->json('data.0.name'));
    }
}