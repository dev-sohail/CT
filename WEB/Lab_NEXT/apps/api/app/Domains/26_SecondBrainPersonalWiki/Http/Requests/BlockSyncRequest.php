<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlockSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'blocks' => ['required', 'array'],
            'blocks.*.id' => ['nullable', 'integer'],
            'blocks.*.type' => ['required', 'string', 'max:100'],
            'blocks.*.content' => ['nullable', 'string'],
            'blocks.*.meta' => ['nullable', 'array'],
            'blocks.*.parent_id' => ['nullable', 'integer'],
            'blocks.*.sort_order' => ['required', 'integer'],
        ];
    }
}
