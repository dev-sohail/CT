<?php

namespace App\Domains\ImportExportAndDataPortability\Services\Providers;

use App\Domains\ContactsAndRelationshipGraph\Models\ContactMethod;
use App\Domains\ContactsAndRelationshipGraph\Models\Person;
use App\Domains\ContactsAndRelationshipGraph\Models\Relationship;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\ImportExportAndDataPortability\Contracts\DataTransferProvider;

class ContactsDataTransferProvider implements DataTransferProvider
{
    public function domain(): string
    {
        return 'contacts';
    }

    public function export(User $user): array
    {
        $people = Person::with(['contactMethods', 'relationships'])->where('user_id', $user->id)->get();

        return [
            'people' => $people->map(function (Person $person) {
                return [
                    'name' => $person->name,
                    'nickname' => $person->nickname,
                    'email' => $person->email,
                    'phone' => $person->phone,
                    'company' => $person->company,
                    'birthday' => $person->birthday?->toDateString(),
                    'avatar_url' => $person->avatar_url,
                    'notes' => $person->notes,
                    'relationship_type' => $person->relationship_type,
                    'metadata' => $person->metadata,
                    'contact_methods' => $person->contactMethods->map(fn (ContactMethod $m) => [
                        'type' => $m->type,
                        'value' => $m->value,
                        'label' => $m->label,
                        'is_primary' => (bool) $m->is_primary,
                    ])->values(),
                ];
            })->values(),
            'relationships' => $people->flatMap(fn (Person $person) => $person->relationships->map(
                fn (Relationship $r) => [
                    'from' => $person->name,
                    'to' => $r->relatedPerson?->name,
                    'type' => $r->type,
                    'note' => $r->note,
                ]
            ))->values(),
        ];
    }

    public function import(User $user, array $data): array
    {
        $counts = ['people' => 0, 'contact_methods' => 0, 'relationships' => 0];

        foreach ($data['people'] ?? [] as $row) {
            $person = Person::create([
                'user_id' => $user->id,
                'name' => $row['name'] ?? null,
                'nickname' => $row['nickname'] ?? null,
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'company' => $row['company'] ?? null,
                'birthday' => isset($row['birthday']) ? \Carbon\Carbon::parse($row['birthday']) : null,
                'avatar_url' => $row['avatar_url'] ?? null,
                'notes' => $row['notes'] ?? null,
                'relationship_type' => $row['relationship_type'] ?? null,
                'metadata' => $row['metadata'] ?? null,
            ]);
            $counts['people']++;

            foreach ($row['contact_methods'] ?? [] as $method) {
                $person->contactMethods()->create([
                    'type' => $method['type'] ?? 'other',
                    'value' => $method['value'] ?? '',
                    'label' => $method['label'] ?? null,
                    'is_primary' => $method['is_primary'] ?? false,
                ]);
                $counts['contact_methods']++;
            }
        }

        // Resolve relationships by name pairs.
        $nameMap = Person::withTrashed()->where('user_id', $user->id)->pluck('id', 'name');
        foreach ($data['relationships'] ?? [] as $row) {
            $from = $nameMap->get($row['from'] ?? null);
            $to = $nameMap->get($row['to'] ?? null);
            if (!$from || !$to || $from === $to) {
                continue;
            }
            Relationship::firstOrCreate([
                'user_id' => $user->id,
                'person_id' => $from,
                'related_person_id' => $to,
                'type' => $row['type'] ?? 'other',
            ], ['note' => $row['note'] ?? null]);
            $counts['relationships']++;
        }

        return $counts;
    }
}