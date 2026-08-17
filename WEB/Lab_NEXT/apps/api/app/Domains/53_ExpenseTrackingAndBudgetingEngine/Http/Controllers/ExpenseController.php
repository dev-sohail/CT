<?php

namespace App\Domains\ExpenseTrackingAndBudgetingEngine\Http\Controllers;

use App\Domains\ExpenseTrackingAndBudgetingEngine\Models\Budget;
use App\Domains\ExpenseTrackingAndBudgetingEngine\Models\Expense;
use App\Domains\ExpenseTrackingAndBudgetingEngine\Http\Resources\ExpenseResource;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class ExpenseController extends ApiController
{
    public function index(Request $request)
    {
        $items = Expense::forUser($request->user()->id)->when($request->input('from'), fn ($q, $d) => $q->whereDate('spent_on', '>=', $d))->when($request->input('to'), fn ($q, $d) => $q->whereDate('spent_on', '<=', $d))->when($request->input('category'), fn ($q, $c) => $q->where('category', $c))->orderByDesc('spent_on')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, ExpenseResource::class);
    }

    public function store(Request $request)
    {
        $expense = Expense::create(array_merge(['user_id' => $request->user()->id], $this->validateExpense($request)));
        return $this->respondCreated(ExpenseResource::make($expense)->toArray($request));
    }

    public function show(Request $request, Expense $expense) { $this->authorizeOwner($request, $expense); return $this->respondSuccess(ExpenseResource::make($expense)->toArray($request)); }
    public function update(Request $request, Expense $expense) { $this->authorizeOwner($request, $expense); $expense->update($this->validateExpense($request, true)); return $this->respondSuccess(ExpenseResource::make($expense)->toArray($request)); }
    public function destroy(Request $request, Expense $expense) { $this->authorizeOwner($request, $expense); $expense->delete(); return $this->respondNoContent(); }

    public function summary(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());
        $items = Expense::forUser($request->user()->id)->whereBetween('spent_on', [$from, $to])->get();
        return $this->respondSuccess(['from' => $from, 'to' => $to, 'total' => (float) $items->sum('amount'), 'count' => $items->count(), 'by_category' => $items->groupBy('category')->map(fn ($g) => (float) $g->sum('amount'))]);
    }

    public function budgetSummary(Request $request)
    {
        $budgets = Budget::forUser($request->user()->id)->where('active', true)->get();
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());
        $spent = Expense::forUser($request->user()->id)->whereBetween('spent_on', [$from, $to])->get()->groupBy('category');
        return $this->respondSuccess($budgets->map(fn (Budget $budget) => ['category' => $budget->category, 'budget' => (float) $budget->amount, 'spent' => (float) $spent->get($budget->category, collect())->sum('amount'), 'remaining' => (float) $budget->amount - (float) $spent->get($budget->category, collect())->sum('amount')])->values());
    }

    public function budget(Request $request)
    {
        $budget = Budget::create(array_merge(['user_id' => $request->user()->id], $request->validate(['category' => ['required', 'string', 'max:64'], 'amount' => ['required', 'numeric', 'min:0'], 'period' => ['sometimes', 'in:weekly,monthly,yearly'], 'starts_on' => ['required', 'date'], 'active' => ['boolean']])));
        return $this->respondCreated($budget);
    }

    private function validateExpense(Request $request, bool $partial = false): array
    {
        return $request->validate(['spent_on' => [$partial ? 'sometimes' : 'required', 'date'], 'description' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'category' => [$partial ? 'sometimes' : 'required', 'string', 'max:64'], 'amount' => ['required', 'numeric', 'min:0'], 'currency' => ['sometimes', 'size:3'], 'payment_method' => ['nullable', 'string', 'max:32'], 'recurring' => ['boolean'], 'tags' => ['nullable', 'array'], 'notes' => ['nullable', 'string']]);
    }

    private function authorizeOwner(Request $request, Expense $expense): void { if ($expense->user_id !== $request->user()->id) abort(404); }
}
