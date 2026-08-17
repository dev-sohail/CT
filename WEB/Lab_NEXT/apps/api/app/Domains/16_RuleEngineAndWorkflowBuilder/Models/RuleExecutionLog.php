<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuleExecutionLog extends Model
{
    protected $table = 'rule_execution_log';

    protected $fillable = [
        'rule_id',
        'triggered',
        'context',
        'results',
    ];

    protected $casts = [
        'triggered' => 'boolean',
        'context' => 'array',
        'results' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class, 'rule_id');
    }
}