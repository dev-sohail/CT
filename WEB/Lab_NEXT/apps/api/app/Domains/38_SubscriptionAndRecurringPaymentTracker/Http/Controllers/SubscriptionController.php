<?php

namespace App\Domains\SubscriptionAndRecurringPaymentTracker\Http\Controllers;

use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use App\Domains\SubscriptionAndRecurringPaymentTracker\Http\Resources\SubscriptionResource;
use App\Domains\SubscriptionAndRecurringPaymentTracker\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends ApiController
{
    public function index(Request $request)
    {
        $items = Subscription::forUser($request->user()->id)
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('category'), fn ($q, $c) => $q->where('category', $c))
            ->when($request->input('upcoming'), fn ($q) => $q
                ->where('status', 'active')
                ->whereNotNull('next_billing_at')
                ->where('next_billing_at', '<=', now()->addDays((int) $request->input('upcoming'))->toDateString()))
            ->orderBy('next_billing_at')
            ->paginate($request->integer('per_page', 25));

        return $this->respondPaginated($items, SubscriptionResource::class);
    }

    public function store(Request $request)
    {
        $sub = Subscription::create(array_merge(['user_id' => $request->user()->id], $this->validateSub($request)));

        return $this->respondCreated(SubscriptionResource::make($sub)->toArray($request));
    }

    public function show(Request $request, Subscription $subscription)
    {
        $this->authorizeOwner($request, $subscription);

        return $this->respondSuccess(SubscriptionResource::make($subscription)->toArray($request));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $this->authorizeOwner($request, $subscription);
        $subscription->update($this->validateSub($request, true));

        return $this->respondSuccess(SubscriptionResource::make($subscription)->toArray($request));
    }

    public function destroy(Request $request, Subscription $subscription)
    {
        $this->authorizeOwner($request, $subscription);
        $subscription->delete();

        return $this->respondNoContent();
    }

    public function stats(Request $request)
    {
        $items = Subscription::forUser($request->user()->id)->get();
        $active = $items->where('status', 'active');

        $monthly = 0.0;
        foreach ($active as $sub) {
            $monthly += $sub->monthlyEquivalent();
        }

        return $this->respondSuccess([
            'total' => $items->count(),
            'active' => $active->count(),
            'cancelled' => $items->where('status', 'cancelled')->count(),
            'monthly_cost' => round($monthly, 2),
            'yearly_cost' => round($monthly * 12, 2),
            'by_category' => $active->groupBy('category')->map(function ($group) {
                $total = 0.0;
                foreach ($group as $sub) {
                    $total += $sub->monthlyEquivalent();
                }
                return ['count' => $group->count(), 'monthly' => round($total, 2)];
            }),
            'upcoming_30d' => $items
                ->filter(fn (Subscription $s) => $s->status === 'active' && $s->next_billing_at && $s->next_billing_at->between(now(), now()->addDays(30)))
                ->count(),
        ]);
    }

    private function validateSub(Request $request, bool $partial = false): array
    {
        $rules = [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:128'],
            'category' => ['nullable', 'string', 'max:64'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'billing_cycle' => ['sometimes', 'in:weekly,monthly,quarterly,yearly,one_time'],
            'started_at' => ['nullable', 'date'],
            'next_billing_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:64'],
            'auto_renew' => ['boolean'],
            'status' => ['sometimes', 'in:active,cancelled,paused,expired'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }

    private function authorizeOwner(Request $request, Subscription $sub): void
    {
        if ($sub->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}