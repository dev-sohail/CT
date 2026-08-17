<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuleAction extends Model
{
    protected $table = 'rule_actions';

    protected $fillable = [
        'rule_id',
        'type',
        'config',
        'order',
    ];

    protected $casts = [
        'config' => 'array',
        'order' => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class, 'rule_id');
    }
}