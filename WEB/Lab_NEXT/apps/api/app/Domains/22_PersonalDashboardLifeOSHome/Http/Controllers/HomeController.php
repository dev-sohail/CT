<?php

namespace App\Domains\PersonalDashboardLifeOSHome\Http\Controllers;

use App\Domains\PersonalDashboardLifeOSHome\Services\LifeOsHomeService;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class HomeController extends ApiController
{
    public function __construct(private LifeOsHomeService $home)
    {
    }

    public function summary(Request $request)
    {
        return $this->respondSuccess($this->home->summary($request->user()));
    }
}