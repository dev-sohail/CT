<?php

namespace Tests\Feature;

use App\Domains\CalendarAndSchedulingKernel\Models\CalendarEvent;
use App\Domains\ContactsAndRelationshipGraph\Models\Person;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\RuleEngineAndWorkflowBuilder\Models\Rule;
use App\Domains\RuleEngineAndWorkflowBuilder\Models\RuleAction;
use App\Domains\RuleEngineAndWorkflowBuilder\Models\RuleCondition;
use Laravel\Sanctum\Sanctum;

class RuleEngineTest extends FeatureTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
    }

    private function makeRule(array $overrides = []): Rule
    {
        return Rule::create(array_merge([
            'user_id' => $this->owner->id,
            'name' => 'Test Rule',
            'is_active' => true,
            'trigger_type' => 'calendar',
            'conditions_logic' => 'all',
        ], $overrides));
    }

    public function test_capabilities_lists_triggers_and_actions(): void
    {
        Sanctum::actingAs($this->owner);
        $res = $this->getJson('/api/v1/rules/capabilities');

        $res->assertOk();
        $this->assertContains('calendar', $res->json('data.triggers'));
        $this->assertContains('contacts', $res->json('data.triggers'));
        $this->assertContains('notify', $res->json('data.actions'));
        $this->assertContains('audit', $res->json('data.actions'));
    }

    public function test_rule_crud(): void
    {
        Sanctum::actingAs($this->owner);

        $created = $this->postJson('/api/v1/rules', [
            'name' => 'Remind before event',
            'trigger_type' => 'calendar',
            'trigger_config' => ['days' => 14],
            'conditions_logic' => 'any',
            'conditions' => [
                ['type' => 'comparison', 'field' => 'next_event_days', 'operator' => 'lte', 'value' => 3],
            ],
            'actions' => [
                ['type' => 'notify', 'config' => ['title' => 'Event soon', 'body' => 'Your event is coming up.']],
            ],
        ]);

        $created->assertCreated();
        $id = $created->json('data.id');
        $this->assertEquals('calendar', $created->json('data.trigger_type'));
        $this->assertCount(1, $created->json('data.conditions'));

        $shown = $this->getJson("/api/v1/rules/{$id}");
        $shown->assertOk();
        $this->assertEquals('Remind before event', $shown->json('data.name'));

        $updated = $this->putJson("/api/v1/rules/{$id}", [
            'name' => 'Renamed',
            'trigger_type' => 'calendar',
            'conditions' => [],
            'actions' => [],
        ]);
        $updated->assertOk();
        $this->assertEquals('Renamed', $updated->json('data.name'));

        $this->deleteJson("/api/v1/rules/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('rules', ['id' => $id]);
    }

    public function test_engine_evaluates_and_fires_notification(): void
    {
        $rule = $this->makeRule();
        RuleCondition::create([
            'rule_id' => $rule->id,
            'type' => 'comparison',
            'field' => 'next_event_days',
            'operator' => 'lte',
            'value' => 3,
            'order' => 0,
        ]);
        RuleAction::create([
            'rule_id' => $rule->id,
            'type' => 'notify',
            'config' => ['title' => 'Soon', 'body' => 'Event is close!'],
            'order' => 0,
        ]);

        CalendarEvent::create([
            'user_id' => $this->owner->id,
            'title' => 'Dentist',
            'starts_at' => now()->addDays(2)->toIso8601String(),
        ]);

        $engine = app(\App\Domains\RuleEngineAndWorkflowBuilder\Services\RuleEngine::class);
        $log = $engine->run($rule);

        $this->assertTrue($log->triggered);
        $this->assertEquals('notify', $log->results[0]['action'] ?? null);
        $this->assertTrue($log->results[0]['result']['sent'] ?? false);
        $this->assertDatabaseHas('notifications', ['data->rule_id' => $rule->id]);
    }

    public function test_engine_does_not_fire_when_conditions_not_met(): void
    {
        $rule = $this->makeRule();
        RuleCondition::create([
            'rule_id' => $rule->id,
            'type' => 'comparison',
            'field' => 'next_event_days',
            'operator' => 'gt',
            'value' => 30,
            'order' => 0,
        ]);
        RuleAction::create([
            'rule_id' => $rule->id,
            'type' => 'notify',
            'config' => ['title' => 'x', 'body' => 'y'],
            'order' => 0,
        ]);

        // No events within 14 days -> next_event_days is null -> gt 30 is false.
        $engine = app(\App\Domains\RuleEngineAndWorkflowBuilder\Services\RuleEngine::class);
        $log = $engine->run($rule);

        $this->assertFalse($log->triggered);
        $this->assertDatabaseMissing('notifications', ['data->rule_id' => $rule->id]);
    }

    public function test_conditions_logic_any(): void
    {
        $rule = $this->makeRule(['conditions_logic' => 'any']);
        RuleCondition::create(['rule_id' => $rule->id, 'type' => 'comparison', 'field' => 'next_event_days', 'operator' => 'eq', 'value' => 999, 'order' => 0]);
        RuleCondition::create(['rule_id' => $rule->id, 'type' => 'comparison', 'field' => 'event_count', 'operator' => 'gt', 'value' => 0, 'order' => 1]);

        CalendarEvent::create(['user_id' => $this->owner->id, 'title' => 'E', 'starts_at' => now()->addDay()->toIso8601String()]);

        $engine = app(\App\Domains\RuleEngineAndWorkflowBuilder\Services\RuleEngine::class);
        $this->assertTrue($engine->evaluate($rule->fresh(), $engine->contextFor($this->owner, 'calendar')));
    }

    public function test_contacts_trigger_context(): void
    {
        Person::create([
            'user_id' => $this->owner->id,
            'name' => 'Mom',
            'birthday' => now()->addDays(5)->toDateString(),
        ]);

        $engine = app(\App\Domains\RuleEngineAndWorkflowBuilder\Services\RuleEngine::class);
        $context = $engine->contextFor($this->owner, 'contacts');

        $this->assertEquals(1, $context['contact_count']);
        $this->assertEquals(1, $context['birthdays_soon_count']);
        $this->assertEquals('Mom', $context['birthdays_soon'][0]['name']);
    }

    public function test_test_endpoint_returns_condition_verdicts(): void
    {
        $rule = $this->makeRule();
        RuleCondition::create(['rule_id' => $rule->id, 'type' => 'comparison', 'field' => 'event_count', 'operator' => 'gt', 'value' => 0, 'order' => 0]);

        CalendarEvent::create(['user_id' => $this->owner->id, 'title' => 'Standup', 'starts_at' => now()->addDay()->toIso8601String()]);

        Sanctum::actingAs($this->owner);
        $res = $this->postJson("/api/v1/rules/{$rule->id}/test");

        $res->assertOk();
        $this->assertTrue($res->json('data.verdict'));
        $this->assertTrue($res->json('data.conditions.0.passed'));
    }

    public function test_run_endpoint_and_logs(): void
    {
        $rule = $this->makeRule();
        RuleCondition::create(['rule_id' => $rule->id, 'type' => 'comparison', 'field' => 'event_count', 'operator' => 'gte', 'value' => 0, 'order' => 0]);
        RuleAction::create(['rule_id' => $rule->id, 'type' => 'audit', 'config' => ['action' => 'rule.triggered', 'subject_type' => 'rule'], 'order' => 0]);

        Sanctum::actingAs($this->owner);
        $run = $this->postJson("/api/v1/rules/{$rule->id}/run");
        $run->assertOk();
        $this->assertTrue($run->json('data.triggered'));
        $this->assertEquals('audit', $run->json('data.results.0.action'));

        $logs = $this->getJson("/api/v1/rules/{$rule->id}/logs");
        $logs->assertOk();
        $this->assertCount(1, $logs->json('data'));
    }

    public function test_guest_cannot_access_rules(): void
    {
        $this->getJson('/api/v1/rules')->assertUnauthorized();
    }
}