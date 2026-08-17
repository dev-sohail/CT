<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\StudyAndCourseTracker\Models\Course;
use Laravel\Sanctum\Sanctum;

class CourseTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_course_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/courses', [
            'title' => 'Laravel Advanced',
            'subject' => 'Programming',
            'hours_target' => 40,
            'provider' => 'Coursera',
        ]);
        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('Laravel Advanced', $created->json('data.title'));

        $updated = $this->putJson("/api/v1/courses/{$id}", ['status' => 'in_progress']);
        $updated->assertOk();
        $this->assertEquals('in_progress', $updated->json('data.status'));
        $this->assertNotNull($updated->json('data.started_at'));

        $this->deleteJson("/api/v1/courses/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('courses', ['id' => $id]);
    }

    public function test_log_hours_increments_and_progress(): void
    {
        $course = Course::create(['user_id' => $this->owner->id, 'title' => 'Math', 'hours_target' => 20]);

        Sanctum::actingAs($this->owner);
        $res = $this->postJson("/api/v1/courses/{$course->id}/log-hours", ['hours' => 5]);

        $res->assertOk();
        $this->assertEquals(5, $res->json('data.hours_spent'));
        $this->assertEquals(25, $res->json('data.progress_percent'));
    }

    public function test_completed_course_sets_completed_at(): void
    {
        $course = Course::create(['user_id' => $this->owner->id, 'title' => 'History', 'status' => 'in_progress']);

        Sanctum::actingAs($this->owner);
        $res = $this->putJson("/api/v1/courses/{$course->id}", ['status' => 'completed']);

        $res->assertOk();
        $this->assertEquals('completed', $res->json('data.status'));
        $this->assertNotNull($res->json('data.completed_at'));
        $this->assertEquals(100, $res->json('data.progress_percent'));
    }

    public function test_stats_aggregates(): void
    {
        Course::create(['user_id' => $this->owner->id, 'title' => 'A', 'status' => 'in_progress', 'hours_spent' => 3, 'subject' => 'Math']);
        Course::create(['user_id' => $this->owner->id, 'title' => 'B', 'status' => 'completed', 'hours_spent' => 7, 'subject' => 'Math']);

        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/courses/stats');

        $res->assertOk();
        $this->assertEquals(2, $res->json('data.total_courses'));
        $this->assertEquals(1, $res->json('data.completed'));
        $this->assertEquals(10, $res->json('data.total_hours_spent'));
    }

    public function test_guest_cannot_access_courses(): void
    {
        $this->getJson('/api/v1/courses')->assertUnauthorized();
    }
}