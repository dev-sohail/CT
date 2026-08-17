<?php

namespace App\Domains\ExpenseTrackingAndBudgetingEngine\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'spent_on' => $this->spent_on?->toDateString(), 'description' => $this->description, 'category' => $this->category, 'amount' => (float) $this->amount, 'currency' => $this->currency, 'payment_method' => $this->payment_method, 'recurring' => (bool) $this->recurring, 'tags' => $this->tags, 'notes' => $this->notes, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
