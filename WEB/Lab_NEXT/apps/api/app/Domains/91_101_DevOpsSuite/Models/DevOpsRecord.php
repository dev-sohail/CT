<?php

namespace App\Domains\DevOpsSuite\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DevOpsRecord extends Model
{
    protected $table = 'devops_records';
    protected $fillable = ['user_id', 'record_type', 'name', 'status', 'environment', 'url', 'description', 'config', 'metadata'];
    protected $casts = ['config' => 'array', 'metadata' => 'array'];
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
