<?php

namespace App\Domains\DashboardAndWidgetFramework\Http\Controllers;

use App\Domains\DashboardAndWidgetFramework\Http\Resources\WidgetLayoutResource;
use App\Domains\DashboardAndWidgetFramework\Http\Resources\WidgetRegistrationResource;
use App\Domains\DashboardAndWidgetFramework\Models\WidgetRegistration;
use App\Domains\DashboardAndWidgetFramework\Services\WidgetRegistry;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class WidgetController extends ApiController
{
    public function __construct(private WidgetRegistry $registry)
    {
    }

    public function index()
    {
        $this->registry->syncRegistrations();

        return $this->respondSuccess(
            WidgetRegistrationResource::collection($this->registry->registered())
        );
    }

    public function data(Request $request, string $key)
    {
        $widget = $this->registry->provide($request->user(), $key);
        if (!$widget) {
            return $this->respondError('Widget not found.', 404);
        }

        return $this->respondSuccess([
            'key' => $widget->key,
            'title' => $widget->title,
            'subtitle' => $widget->subtitle,
            'refresh_interval' => $widget->refresh_interval ?? $this->registry->provider($key)?->refreshInterval(),
            'data' => $widget->data,
        ]);
    }

    public function layout(Request $request)
    {
        $this->registry->syncRegistrations();
        $this->registry->ensureDefaultLayout($request->user());

        return $this->respondSuccess(
            WidgetLayoutResource::collection($this->registry->layout($request->user()))
        );
    }

    public function saveLayout(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.widget_key' => ['required', 'string'],
            'items.*.position_x' => ['nullable', 'integer', 'min:0'],
            'items.*.position_y' => ['nullable', 'integer', 'min:0'],
            'items.*.width' => ['nullable', 'integer', 'min:1'],
            'items.*.height' => ['nullable', 'integer', 'min:1'],
            'items.*.enabled' => ['nullable', 'boolean'],
            'items.*.refresh_interval' => ['nullable', 'integer', 'min:30'],
        ]);

        $this->registry->setLayout($request->user(), $validated['items']);

        return $this->respondSuccess(
            WidgetLayoutResource::collection($this->registry->layout($request->user()))
        );
    }

    public function add(Request $request, string $key)
    {
        $item = $this->registry->addToLayout($request->user(), $key);
        if (!$item) {
            return $this->respondError('Widget not found.', 404);
        }

        return $this->respondCreated(WidgetLayoutResource::make($item));
    }

    public function remove(Request $request, string $key)
    {
        $this->registry->removeFromLayout($request->user(), $key);

        return $this->respondNoContent();
    }
}