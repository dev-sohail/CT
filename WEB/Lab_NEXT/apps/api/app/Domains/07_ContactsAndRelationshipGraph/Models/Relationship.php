<?php

namespace App\Domains\ContactsAndRelationshipGraph\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Relationship extends Model
{
    protected $table = 'relationships';

    protected $fillable = [
        'user_id',
        'person_id',
        'related_person_id',
        'type',
        'note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function relatedPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'related_person_id');
    }
}