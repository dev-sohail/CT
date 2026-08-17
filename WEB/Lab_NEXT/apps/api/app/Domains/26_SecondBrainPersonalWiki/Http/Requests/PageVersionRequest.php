<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PageVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'blocks' => ['nullable', 'array'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
