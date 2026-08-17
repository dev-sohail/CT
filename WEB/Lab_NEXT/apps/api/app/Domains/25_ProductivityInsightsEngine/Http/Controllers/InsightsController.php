<?php

namespace App\Domains\ProductivityInsightsEngine\Http\Controllers;

use App\Domains\ProductivityInsightsEngine\Services\ProductivityInsightsService;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class InsightsController extends ApiController
{
    public function __construct(private ProductivityInsightsService $insights)
    {
    }

    public function overview(Request $request)
    {
        return $this->respondSuccess($this->insights->overview($request->user()));
    }

    public function completion(Request $request)
    {
        return $this->respondSuccess([
            'by_scope' => $this->insights->completionByScope($request->user()),
            'by_priority' => $this->insights->completionByPriority($request->user()),
        ]);
    }

    public function trends(Request $request)
    {
        $days = min(90, max(1, (int) $request->input('days', 14)));

        return $this->respondSuccess($this->insights->completionTrend($request->user(), $days));
    }

    public function focus(Request $request)
    {
        $days = min(90, max(1, (int) $request->input('days', 7)));

        return $this->respondSuccess($this->insights->focusTime($request->user(), $days));
    }
}