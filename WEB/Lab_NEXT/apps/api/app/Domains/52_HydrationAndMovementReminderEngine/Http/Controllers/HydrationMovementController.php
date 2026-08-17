<?php

namespace App\Domains\HydrationAndMovementReminderEngine\Http\Controllers;

use App\Domains\HydrationAndMovementReminderEngine\Models\HydrationLog;
use App\Domains\HydrationAndMovementReminderEngine\Models\MovementLog;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class HydrationMovementController extends ApiController
{
    public function hydration(Request $request)
    {
        $data = $request->validate(['drank_at' => ['sometimes', 'date'], 'milliliters' => ['required', 'integer', 'min:1'], 'beverage' => ['sometimes', 'string', 'max:32']]);
        $data['drank_at'] ??= now()->toDateTimeString();
        return $this->respondCreated(HydrationLog::create(array_merge(['user_id' => $request->user()->id], $data)));
    }

    public function movement(Request $request)
    {
        $data = $request->validate(['moved_at' => ['sometimes', 'date'], 'minutes' => ['required', 'integer', 'min:1'], 'activity' => ['sometimes', 'string', 'max:64'], 'steps' => ['nullable', 'integer', 'min:0'], 'notes' => ['nullable', 'string']]);
        $data['moved_at'] ??= now()->toDateTimeString();
        return $this->respondCreated(MovementLog::create(array_merge(['user_id' => $request->user()->id], $data)));
    }

    public function today(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $hydration = HydrationLog::forUser($request->user()->id)->whereDate('drank_at', $date)->get();
        $movement = MovementLog::forUser($request->user()->id)->whereDate('moved_at', $date)->get();
        $water = $hydration->where('beverage', 'water')->sum('milliliters');
        return $this->respondSuccess(['date' => $date, 'hydration' => ['entries' => $hydration, 'milliliters' => (int) $hydration->sum('milliliters'), 'water_milliliters' => (int) $water], 'movement' => ['entries' => $movement, 'minutes' => (int) $movement->sum('minutes'), 'steps' => (int) $movement->sum('steps')] ]);
    }

    public function stats(Request $request)
    {
        $hydration = HydrationLog::forUser($request->user()->id)->get();
        $movement = MovementLog::forUser($request->user()->id)->get();
        return $this->respondSuccess(['hydration_entries' => $hydration->count(), 'total_milliliters' => (int) $hydration->sum('milliliters'), 'movement_entries' => $movement->count(), 'total_movement_minutes' => (int) $movement->sum('minutes'), 'total_steps' => (int) $movement->sum('steps')]);
    }
}
