<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rule extends Model
{
    protected $table = 'rules';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_active',
        'trigger_type',
        'trigger_config',
        'conditions_logic',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trigger_config' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(RuleCondition::class, 'rule_id')->orderBy('order');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(RuleAction::class, 'rule_id')->orderBy('order');
    }

    public function executionLogs(): HasMany
    {
        return $this->hasMany(RuleExecutionLog::class, 'rule_id')->orderByDesc('created_at');
    }
}