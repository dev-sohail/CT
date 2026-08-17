<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'done' => ['sometimes', 'boolean'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'link_page_id' => ['nullable', 'integer', 'exists:wiki_pages,id'],
        ];
    }
}
