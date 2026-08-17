<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $table = 'identity_permissions';

    protected $fillable = [
        'name',
        'slug',
        'group',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'identity_role_permission')
            ->withTimestamps();
    }
}
