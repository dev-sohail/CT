<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\ProjectRoadmapPlanner\Models\Project;
use Laravel\Sanctum\Sanctum;

class ProjectTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_project_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/roadmap/projects', [
            'name' => 'Website relaunch',
            'status' => 'in_progress',
            'start_at' => '2026-09-01T00:00:00Z',
            'target_end_at' => '2026-12-01T00:00:00Z',
            'priority' => 'high',
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('Website relaunch', $created->json('data.name'));

        $updated = $this->putJson("/api/v1/roadmap/projects/{$id}", ['status' => 'completed']);
        $updated->assertOk();
        $this->assertEquals('completed', $updated->json('data.status'));
        $this->assertEquals(100, $updated->json('data.milestone_progress'));

        $this->deleteJson("/api/v1/roadmap/projects/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('projects', ['id' => $id]);
    }

    public function test_milestones_and_progress(): void
    {
        $project = Project::create(['user_id' => $this->owner->id, 'name' => 'App', 'status' => 'in_progress']);

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/v1/roadmap/projects/{$project->id}/milestones", ['title' => 'Design'])  ->assertCreated();
        $this->postJson("/api/v1/roadmap/projects/{$project->id}/milestones", ['title' => 'Build'])   ->assertCreated();

        $list = $this->getJson("/api/v1/roadmap/projects/{$project->id}/milestones");
        $list->assertOk();
        $this->assertCount(2, $list->json('data'));

        $first = $list->json('data.0.id');
        $updated = $this->putJson("/api/v1/roadmap/projects/{$project->id}/milestones/{$first}", ['status' => 'completed']);
        $updated->assertOk();
        $this->assertEquals('completed', $updated->json('data.status'));

        $shown = $this->getJson("/api/v1/roadmap/projects/{$project->id}");
        $this->assertEquals(50, $shown->json('data.milestone_progress'));

        $this->deleteJson("/api/v1/roadmap/projects/{$project->id}/milestones/{$first}")->assertNoContent();
        $this->assertDatabaseMissing('project_milestones', ['id' => $first]);
    }

    public function test_timeline_returns_gantt_rows(): void
    {
        Project::create([
            'user_id' => $this->owner->id,
            'name' => 'Alpha',
            'status' => 'in_progress',
            'start_at' => '2026-09-01T00:00:00Z',
            'target_end_at' => '2026-11-01T00:00:00Z',
        ]);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/roadmap/timeline');

        $res->assertOk();
        $this->assertCount(1, $res->json('data.rows'));
        $this->assertEquals('Alpha', $res->json('data.rows.0.project.name'));
        $this->assertNotNull($res->json('data.earliest'));
        $this->assertNotNull($res->json('data.latest'));
    }

    public function test_filter_by_status(): void
    {
        Project::create(['user_id' => $this->owner->id, 'name' => 'Active', 'status' => 'in_progress']);
        Project::create(['user_id' => $this->owner->id, 'name' => 'Done', 'status' => 'completed']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/roadmap/projects?status=in_progress');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Active', $res->json('data.0.name'));
    }

    public function test_guest_cannot_access_projects(): void
    {
        $this->getJson('/api/v1/roadmap/projects')->assertUnauthorized();
    }
}