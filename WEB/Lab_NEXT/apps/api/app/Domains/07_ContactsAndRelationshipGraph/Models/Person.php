<?php

namespace App\Domains\ContactsAndRelationshipGraph\Models;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'user_id',
        'name',
        'nickname',
        'email',
        'phone',
        'company',
        'birthday',
        'avatar_url',
        'notes',
        'relationship_type',
        'metadata',
    ];

    protected $casts = [
        'birthday' => 'date',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contactMethods(): HasMany
    {
        return $this->hasMany(ContactMethod::class, 'person_id');
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'person_id');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (!$term) {
            return $query;
        }
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('nickname', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('company', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        if (!$type) {
            return $query;
        }
        return $query->where('relationship_type', $type);
    }

    public function scopeBirthdaySoon(Builder $query, int $days = 30): Builder
    {
        $now = now();
        $end = $now->copy()->addDays($days);

        return $query->whereNotNull('birthday')
            ->whereRaw('DAYOFYEAR(birthday) >= DAYOFYEAR(?)', [$now])
            ->whereRaw('DAYOFYEAR(birthday) <= DAYOFYEAR(?)', [$end]);
    }
}