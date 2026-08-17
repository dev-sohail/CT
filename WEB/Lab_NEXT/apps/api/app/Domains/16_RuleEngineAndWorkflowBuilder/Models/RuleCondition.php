<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuleCondition extends Model
{
    protected $table = 'rule_conditions';

    protected $fillable = [
        'rule_id',
        'type',
        'field',
        'operator',
        'value',
        'order',
    ];

    protected $casts = [
        'value' => 'json',
        'order' => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class, 'rule_id');
    }
}