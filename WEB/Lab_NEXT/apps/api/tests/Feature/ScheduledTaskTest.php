<?php

namespace Tests\Feature;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\ScheduledTaskAndCronManager\Models\ScheduledTask;
use App\Domains\ScheduledTaskAndCronManager\Services\CronParser;
use Laravel\Sanctum\Sanctum;

class ScheduledTaskTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    public function test_cron_parser_handles_standard_expressions(): void
    {
        $parser = app(CronParser::class);

        $this->assertTrue($parser->isExpressionValid('0 9 * * *'));
        $this->assertTrue($parser->isExpressionValid('*/15 * * * *'));
        $this->assertTrue($parser->isExpressionValid('30 8 * * 1-5'));
        $this->assertTrue($parser->isExpressionValid('0 12 * * MON'));
        $this->assertFalse($parser->isExpressionValid('99 99 * * *'));
        $this->assertFalse($parser->isExpressionValid('* * * *'));

        $next = $parser->nextRun('0 9 * * *', now()->setTime(8, 0));
        $this->assertEquals('09:00', $next->format('H:i'));
    }

    public function test_next_run_computes_for_daily_expression(): void
    {
        $parser = app(CronParser::class);
        $after = now()->setTime(3, 0);
        $next = $parser->nextRun('0 2 * * *', $after);
        $this->assertTrue($next->greaterThan($after));
        $this->assertEquals('02:00', $next->format('H:i'));
    }

    public function test_validate_expression_endpoint(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->postJson('/api/v1/cron/validate', ['expression' => '0 6 * * *']);

        $res->assertOk();
        $this->assertTrue($res->json('data.valid'));
        $this->assertNotNull($res->json('data.next_run_at'));
    }

    public function test_create_task_computes_next_run(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->postJson('/api/v1/cron/tasks', [
            'name' => 'Daily backup',
            'command' => 'ctlab:backup',
            'cron_expression' => '0 2 * * *',
        ]);

        $res->assertCreated();
        $this->assertEquals('ctlab:backup', $res->json('data.command'));
        $this->assertNotNull($res->json('data.next_run_at'));
    }

    public function test_create_task_rejects_invalid_cron(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->postJson('/api/v1/cron/tasks', [
            'name' => 'Bad',
            'command' => 'ctlab:backup',
            'cron_expression' => 'not a cron',
        ]);

        $res->assertUnprocessable();
    }

    public function test_task_crud_and_run(): void
    {
        Sanctum::actingAs($this->owner);

        $task = ScheduledTask::create([
            'user_id' => $this->owner->id,
            'name' => 'Rules',
            'command' => 'rules:run',
            'cron_expression' => '*/5 * * * *',
            'is_active' => true,
        ]);

        $run = $this->postJson("/api/v1/cron/tasks/{$task->id}/run");
        $run->assertOk();
        $this->assertContains($run->json('data.log.status'), ['success', 'failed']);
        $this->assertNotNull($run->json('data.task.last_run_at'));

        $logs = $this->getJson("/api/v1/cron/tasks/{$task->id}/logs");
        $logs->assertOk();
        $this->assertCount(1, $logs->json('data'));

        $list = $this->getJson('/api/v1/cron/tasks');
        $list->assertOk();
        $this->assertCount(1, $list->json('data'));

        $update = $this->putJson("/api/v1/cron/tasks/{$task->id}", ['name' => 'Renamed task']);
        $update->assertOk();
        $this->assertEquals('Renamed task', $update->json('data.name'));

        $this->deleteJson("/api/v1/cron/tasks/{$task->id}")->assertNoContent();
        $this->assertDatabaseMissing('scheduled_tasks', ['id' => $task->id]);
    }

    public function test_inactive_task_has_no_next_run(): void
    {
        $task = ScheduledTask::create([
            'user_id' => $this->owner->id,
            'name' => 'Paused',
            'command' => 'rules:run',
            'cron_expression' => '0 9 * * *',
            'is_active' => false,
        ]);

        $scheduler = app(\App\Domains\ScheduledTaskAndCronManager\Services\TaskScheduler::class);
        $this->assertNull($scheduler->computeNextRun($task));
    }

    public function test_guest_cannot_access_tasks(): void
    {
        $this->getJson('/api/v1/cron/tasks')->assertUnauthorized();
    }
}