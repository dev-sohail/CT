<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Services;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\RuleEngineAndWorkflowBuilder\Contracts\Action;
use App\Domains\RuleEngineAndWorkflowBuilder\Contracts\Trigger;
use App\Domains\RuleEngineAndWorkflowBuilder\Models\Rule;
use App\Domains\RuleEngineAndWorkflowBuilder\Models\RuleExecutionLog;

class RuleEngine
{
    /** @var array<string, Trigger> */
    private array $triggers = [];

    /** @var array<string, Action> */
    private array $actions = [];

    public function __construct(
        private ConditionEvaluator $conditionEvaluator,
        array $triggers = [],
        array $actions = [],
    ) {
        foreach ($triggers as $trigger) {
            $this->registerTrigger($trigger);
        }
        foreach ($actions as $action) {
            $this->registerAction($action);
        }
    }

    public function registerTrigger(Trigger $trigger): void
    {
        $this->triggers[$trigger->key()] = $trigger;
    }

    public function registerAction(Action $action): void
    {
        $this->actions[$action->key()] = $action;
    }

    public function triggerKeys(): array
    {
        return array_keys($this->triggers);
    }

    public function actionKeys(): array
    {
        return array_keys($this->actions);
    }

    /**
     * Build the context array a given trigger produces.
     */
    public function contextFor(User $user, string $triggerType, array $config = []): array
    {
        $trigger = $this->triggers[$triggerType] ?? null;
        if (!$trigger) {
            throw new \InvalidArgumentException("Unknown trigger: {$triggerType}");
        }
        return $trigger->buildContext($user, $config);
    }

    /**
     * Evaluate a rule's conditions against a context. Returns bool.
     */
    public function evaluate(Rule $rule, array $context): bool
    {
        $conditions = $rule->conditions()->get();
        if ($conditions->isEmpty()) {
            return true;
        }

        $results = $conditions->map(
            fn ($c) => $this->conditionEvaluator->matches($c, $context)
        );

        return $rule->conditions_logic === 'any' ? $results->contains(true) : $results->every(fn ($r) => $r === true);
    }

    /**
     * Dry-run: return per-condition verdicts for debugging.
     */
    public function test(Rule $rule, array $context): array
    {
        return $rule->conditions()
            ->get()
            ->map(function ($c) use ($context) {
                $actual = data_get($context, $c->field);
                return [
                    'field' => $c->field,
                    'operator' => $c->operator,
                    'expected' => $c->value,
                    'actual' => $actual,
                    'passed' => $this->conditionEvaluator->matches($c, $context),
                ];
            })
            ->all();
    }

    /**
     * Run a single rule: build context, evaluate, dispatch actions, log.
     */
    public function run(Rule $rule): RuleExecutionLog
    {
        $context = $this->contextFor($rule->user, $rule->trigger_type, $rule->trigger_config ?? []);
        $triggered = $this->evaluate($rule, $context);
        $results = [];

        if ($triggered) {
            foreach ($rule->actions()->get() as $ruleAction) {
                $action = $this->actions[$ruleAction->type] ?? null;
                if (!$action) {
                    $results[] = ['action' => $ruleAction->type, 'error' => 'Unknown action type'];
                    continue;
                }
                $config = array_merge($ruleAction->config ?? [], ['rule_id' => $rule->id]);
                $results[] = ['action' => $ruleAction->type, 'result' => $action->execute($rule->user, $config, $context)];
            }
        }

        return RuleExecutionLog::create([
            'rule_id' => $rule->id,
            'triggered' => $triggered,
            'context' => $context,
            'results' => $results,
        ]);
    }

    /**
     * Run all active rules for a user (optionally filtered by trigger).
     */
    public function runForUser(User $user, ?string $triggerType = null): array
    {
        $query = Rule::with(['conditions', 'actions'])->where('user_id', $user->id)->where('is_active', true);
        if ($triggerType) {
            $query->where('trigger_type', $triggerType);
        }

        $logs = [];
        foreach ($query->get() as $rule) {
            $logs[] = $this->run($rule);
        }
        return $logs;
    }
}