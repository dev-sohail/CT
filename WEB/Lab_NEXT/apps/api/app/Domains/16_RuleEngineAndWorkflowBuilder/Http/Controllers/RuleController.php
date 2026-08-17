<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Http\Controllers;

use App\Domains\RuleEngineAndWorkflowBuilder\Http\Resources\RuleExecutionLogResource;
use App\Domains\RuleEngineAndWorkflowBuilder\Http\Resources\RuleResource;
use App\Domains\RuleEngineAndWorkflowBuilder\Models\Rule;
use App\Domains\RuleEngineAndWorkflowBuilder\Models\RuleAction;
use App\Domains\RuleEngineAndWorkflowBuilder\Models\RuleCondition;
use App\Domains\RuleEngineAndWorkflowBuilder\Models\RuleExecutionLog;
use App\Domains\RuleEngineAndWorkflowBuilder\Services\RuleEngine;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class RuleController extends ApiController
{
    public function __construct(private RuleEngine $engine)
    {
    }

    public function capabilities()
    {
        return $this->respondSuccess([
            'triggers' => $this->engine->triggerKeys(),
            'actions' => $this->engine->actionKeys(),
        ]);
    }

    public function index(Request $request)
    {
        $rules = Rule::with(['conditions', 'actions'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get();

        return $this->respondSuccess(RuleResource::collection($rules));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRule($request);

        $rule = Rule::create(array_merge(
            ['user_id' => $request->user()->id],
            collect($validated)->except(['conditions', 'actions'])->all()
        ));

        $this->syncChildren($rule, $validated);

        return $this->respondCreated(RuleResource::make($rule->load(['conditions', 'actions'])));
    }

    public function show(Request $request, Rule $rule)
    {
        if ($rule->user_id !== $request->user()->id) {
            return $this->respondError('Rule not found.', 404);
        }
        return $this->respondSuccess(RuleResource::make($rule->load(['conditions', 'actions'])));
    }

    public function update(Request $request, Rule $rule)
    {
        if ($rule->user_id !== $request->user()->id) {
            return $this->respondError('Rule not found.', 404);
        }

        $validated = $this->validateRule($request);

        $rule->update(collect($validated)->except(['conditions', 'actions'])->all());
        $this->syncChildren($rule, $validated);

        return $this->respondSuccess(RuleResource::make($rule->fresh(['conditions', 'actions'])));
    }

    public function destroy(Request $request, Rule $rule)
    {
        if ($rule->user_id !== $request->user()->id) {
            return $this->respondError('Rule not found.', 404);
        }
        $rule->delete();
        return $this->respondNoContent();
    }

    public function test(Request $request, Rule $rule)
    {
        if ($rule->user_id !== $request->user()->id) {
            return $this->respondError('Rule not found.', 404);
        }

        $context = $request->input('context')
            ?? $this->engine->contextFor($request->user(), $rule->trigger_type, $rule->trigger_config ?? []);

        return $this->respondSuccess([
            'verdict' => $this->engine->evaluate($rule, $context),
            'conditions' => $this->engine->test($rule, $context),
        ]);
    }

    public function run(Request $request, Rule $rule)
    {
        if ($rule->user_id !== $request->user()->id) {
            return $this->respondError('Rule not found.', 404);
        }

        $log = $this->engine->run($rule);

        return $this->respondSuccess(RuleExecutionLogResource::make($log));
    }

    public function logs(Request $request, Rule $rule)
    {
        if ($rule->user_id !== $request->user()->id) {
            return $this->respondError('Rule not found.', 404);
        }

        $logs = RuleExecutionLog::where('rule_id', $rule->id)->orderByDesc('created_at')->limit(20)->get();

        return $this->respondSuccess(RuleExecutionLogResource::collection($logs));
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function validateRule(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'trigger_type' => ['required', 'string', 'in:' . implode(',', $this->engine->triggerKeys())],
            'trigger_config' => ['nullable', 'array'],
            'conditions_logic' => ['sometimes', 'in:all,any'],
            'conditions' => ['nullable', 'array'],
            'conditions.*.type' => ['required_with:conditions', 'in:comparison,date'],
            'conditions.*.field' => ['required_with:conditions', 'string'],
            'conditions.*.operator' => ['required_with:conditions', 'string'],
            'conditions.*.value' => ['nullable'],
            'actions' => ['nullable', 'array'],
            'actions.*.type' => ['required_with:actions', 'in:' . implode(',', $this->engine->actionKeys())],
            'actions.*.config' => ['nullable', 'array'],
        ]);

        return $data;
    }

    private function syncChildren(Rule $rule, array $validated): void
    {
        $rule->conditions()->delete();
        foreach ($validated['conditions'] ?? [] as $i => $c) {
            RuleCondition::create([
                'rule_id' => $rule->id,
                'type' => $c['type'] ?? 'comparison',
                'field' => $c['field'],
                'operator' => $c['operator'],
                'value' => $c['value'] ?? null,
                'order' => $i,
            ]);
        }

        $rule->actions()->delete();
        foreach ($validated['actions'] ?? [] as $i => $a) {
            RuleAction::create([
                'rule_id' => $rule->id,
                'type' => $a['type'],
                'config' => $a['config'] ?? null,
                'order' => $i,
            ]);
        }
    }
}