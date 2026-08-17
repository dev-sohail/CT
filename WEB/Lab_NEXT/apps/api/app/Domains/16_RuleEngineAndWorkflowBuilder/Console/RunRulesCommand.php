<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Console;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\RuleEngineAndWorkflowBuilder\Services\RuleEngine;
use Illuminate\Console\Command;

class RunRulesCommand extends Command
{
    protected $signature = 'rules:run
        {--user= : Only evaluate rules for this user id}
        {--trigger= : Only evaluate rules of this trigger type}';

    protected $description = 'Evaluate all active automation rules';

    public function handle(RuleEngine $engine): int
    {
        $query = User::query();
        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $ran = 0;
        $triggered = 0;

        foreach ($users as $user) {
            $logs = $engine->runForUser($user, $this->option('trigger'));
            $ran += count($logs);
            $triggered += collect($logs)->where('triggered', true)->count();
        }

        $this->info("Evaluated {$ran} rules; {$triggered} triggered.");

        return self::SUCCESS;
    }
}