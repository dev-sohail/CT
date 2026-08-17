<?php

namespace App\Domains\ContactsAndRelationshipGraph\Http\Controllers;

use App\Domains\ContactsAndRelationshipGraph\Http\Resources\PersonResource;
use App\Domains\ContactsAndRelationshipGraph\Http\Resources\RelationshipResource;
use App\Domains\ContactsAndRelationshipGraph\Models\ContactMethod;
use App\Domains\ContactsAndRelationshipGraph\Models\Person;
use App\Domains\ContactsAndRelationshipGraph\Models\Relationship;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class PersonController extends ApiController
{
    public function index(Request $request)
    {
        $people = Person::forUser($request->user()->id)
            ->search($request->input('q'))
            ->ofType($request->input('type'))
            ->orderBy('name')
            ->get();

        return $this->respondSuccess(PersonResource::collection($people));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'company' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'avatar_url' => ['nullable', 'string', 'max:2048'],
            'notes' => ['nullable', 'string'],
            'relationship_type' => ['nullable', 'string', 'max:32'],
            'metadata' => ['nullable', 'array'],
            'contact_methods' => ['nullable', 'array'],
            'contact_methods.*.type' => ['required_with:contact_methods', 'string', 'max:32'],
            'contact_methods.*.value' => ['required_with:contact_methods', 'string', 'max:512'],
            'contact_methods.*.label' => ['nullable', 'string', 'max:128'],
            'contact_methods.*.is_primary' => ['boolean'],
        ]);

        $methods = $validated['contact_methods'] ?? [];
        unset($validated['contact_methods']);

        $person = Person::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        foreach ($methods as $method) {
            $person->contactMethods()->create($method);
        }

        return $this->respondCreated(PersonResource::make($person->load('contactMethods')));
    }

    public function show(Request $request, Person $person)
    {
        if ($person->user_id !== $request->user()->id) {
            return $this->respondError('Person not found.', 404);
        }

        return $this->respondSuccess(PersonResource::make($person->load(['contactMethods', 'relationships.relatedPerson'])));
    }

    public function update(Request $request, Person $person)
    {
        if ($person->user_id !== $request->user()->id) {
            return $this->respondError('Person not found.', 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'company' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'avatar_url' => ['nullable', 'string', 'max:2048'],
            'notes' => ['nullable', 'string'],
            'relationship_type' => ['nullable', 'string', 'max:32'],
            'metadata' => ['nullable', 'array'],
        ]);

        $person->update($validated);

        return $this->respondSuccess(PersonResource::make($person->fresh()));
    }

    public function destroy(Request $request, Person $person)
    {
        if ($person->user_id !== $request->user()->id) {
            return $this->respondError('Person not found.', 404);
        }

        $person->delete();

        return $this->respondNoContent();
    }

    // ------------------------------------------------------------------
    // Relationships (graph edges)
    // ------------------------------------------------------------------

    public function relationships(Request $request, Person $person)
    {
        if ($person->user_id !== $request->user()->id) {
            return $this->respondError('Person not found.', 404);
        }

        $rels = $person->relationships()->with('relatedPerson')->get();

        return $this->respondSuccess(RelationshipResource::collection($rels));
    }

    public function addRelationship(Request $request, Person $person)
    {
        if ($person->user_id !== $request->user()->id) {
            return $this->respondError('Person not found.', 404);
        }

        $validated = $request->validate([
            'related_person_id' => ['required', 'exists:people,id'],
            'type' => ['required', 'string', 'max:32'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validated['related_person_id'] == $person->id) {
            return $this->respondError('A person cannot relate to themselves.', 422);
        }

        $relationship = Relationship::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'person_id' => $person->id,
                'related_person_id' => $validated['related_person_id'],
                'type' => $validated['type'],
            ],
            ['note' => $validated['note'] ?? null]
        );

        return $this->respondCreated(RelationshipResource::make($relationship->load('relatedPerson')));
    }

    public function removeRelationship(Request $request, Person $person, Relationship $relationship)
    {
        if ($person->user_id !== $request->user()->id || $relationship->person_id !== $person->id) {
            return $this->respondError('Relationship not found.', 404);
        }

        $relationship->delete();

        return $this->respondNoContent();
    }

    public function birthdaySoon(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $people = Person::forUser($request->user()->id)->birthdaySoon($days)->get();

        return $this->respondSuccess(PersonResource::collection($people));
    }
}