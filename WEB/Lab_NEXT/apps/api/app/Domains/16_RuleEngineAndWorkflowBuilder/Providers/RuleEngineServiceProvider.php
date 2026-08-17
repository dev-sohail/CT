<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Providers;

use App\Domains\RuleEngineAndWorkflowBuilder\Actions\AuditAction;
use App\Domains\RuleEngineAndWorkflowBuilder\Actions\NotifyAction;
use App\Domains\RuleEngineAndWorkflowBuilder\Console\RunRulesCommand;
use App\Domains\RuleEngineAndWorkflowBuilder\Services\ConditionEvaluator;
use App\Domains\RuleEngineAndWorkflowBuilder\Services\RuleEngine;
use App\Domains\RuleEngineAndWorkflowBuilder\Triggers\CalendarTrigger;
use App\Domains\RuleEngineAndWorkflowBuilder\Triggers\ContactsTrigger;
use Illuminate\Support\ServiceProvider;

class RuleEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RuleEngine::class, function ($app) {
            return new RuleEngine(
                $app->make(ConditionEvaluator::class),
                [
                    $app->make(CalendarTrigger::class),
                    $app->make(ContactsTrigger::class),
                ],
                [
                    $app->make(NotifyAction::class),
                    $app->make(AuditAction::class),
                ]
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([RunRulesCommand::class]);
        }
    }
}