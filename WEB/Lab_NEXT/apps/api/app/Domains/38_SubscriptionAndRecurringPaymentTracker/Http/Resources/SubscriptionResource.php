<?php

namespace App\Domains\SubscriptionAndRecurringPaymentTracker\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company' => $this->company,
            'category' => $this->category,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'billing_cycle' => $this->billing_cycle,
            'monthly_equivalent' => round($this->monthlyEquivalent(), 2),
            'started_at' => $this->started_at?->toDateString(),
            'next_billing_at' => $this->next_billing_at?->toDateString(),
            'payment_method' => $this->payment_method,
            'auto_renew' => (bool) $this->auto_renew,
            'status' => $this->status,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}