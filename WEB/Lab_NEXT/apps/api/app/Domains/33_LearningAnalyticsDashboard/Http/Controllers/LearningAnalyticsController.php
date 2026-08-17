<?php

namespace App\Domains\LearningAnalyticsDashboard\Http\Controllers;

use App\Domains\LearningAnalyticsDashboard\Services\LearningAnalyticsService;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class LearningAnalyticsController extends ApiController
{
    public function __construct(private LearningAnalyticsService $analytics)
    {
    }

    public function overview(Request $request)
    {
        return $this->respondSuccess($this->analytics->overview($request->user()));
    }

    public function streaks(Request $request)
    {
        return $this->respondSuccess($this->analytics->studyStreak($request->user()));
    }

    public function retention(Request $request)
    {
        $days = min(90, max(1, (int) $request->input('days', 14)));

        return $this->respondSuccess($this->analytics->retentionCurve($request->user(), $days));
    }
}