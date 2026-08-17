<?php

namespace Tests\Feature;

use App\Domains\CodeSnippetManagerAndPackageIndex\Models\CodeSnippet;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Laravel\Sanctum\Sanctum;

class SnippetTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_snippet_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/snippets', [
            'title' => 'Tail recursion',
            'language' => 'php',
            'code' => 'function sum($n, $acc = 0) { ... }',
            'tags' => ['recursion', 'php'],
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('php', $created->json('data.language'));

        $updated = $this->putJson("/api/v1/snippets/{$id}", ['language' => 'typescript']);
        $updated->assertOk();
        $this->assertEquals('typescript', $updated->json('data.language'));

        $this->deleteJson("/api/v1/snippets/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('code_snippets', ['id' => $id]);
    }

    public function test_favorite_toggle(): void
    {
        $snippet = CodeSnippet::create(['user_id' => $this->owner->id, 'title' => 'A', 'code' => 'x', 'language' => 'php']);

        Sanctum::actingAs($this->owner);
        $res = $this->postJson("/api/v1/snippets/{$snippet->id}/favorite");
        $res->assertOk();
        $this->assertTrue($res->json('data.favorite'));

        $res2 = $this->postJson("/api/v1/snippets/{$snippet->id}/favorite");
        $this->assertFalse($res2->json('data.favorite'));
    }

    public function test_search_and_filter(): void
    {
        CodeSnippet::create(['user_id' => $this->owner->id, 'title' => 'Blade directive', 'language' => 'php', 'code' => 'Blade::directive', 'tags' => ['laravel']]);
        CodeSnippet::create(['user_id' => $this->owner->id, 'title' => 'Middleware', 'language' => 'php', 'code' => 'class Middleware', 'tags' => ['laravel']]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/snippets?q=blade');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Blade directive', $res->json('data.0.title'));
    }

    public function test_package_index(): void
    {
        CodeSnippet::create(['user_id' => $this->owner->id, 'title' => 'Pkg', 'code' => 'x', 'package_type' => 'composer', 'package_name' => 'ctlab/kit', 'package_version' => '1.0.0']);
        CodeSnippet::create(['user_id' => $this->owner->id, 'title' => 'Plain', 'code' => 'y']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/snippets/packages');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('ctlab/kit', $res->json('data.0.name'));
    }

    public function test_guest_cannot_access_snippets(): void
    {
        $this->getJson('/api/v1/snippets')->assertUnauthorized();
    }
}