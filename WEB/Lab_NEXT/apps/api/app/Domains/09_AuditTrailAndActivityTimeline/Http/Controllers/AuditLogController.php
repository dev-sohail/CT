<?php

namespace App\Domains\AuditTrailAndActivityTimeline\Http\Controllers;

use App\Domains\AuditTrailAndActivityTimeline\Http\Resources\AuditLogResource;
use App\Domains\AuditTrailAndActivityTimeline\Models\AuditLog;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class AuditLogController extends ApiController
{
    public function index(Request $request)
    {
        $query = AuditLog::where('user_id', $request->user()->id);

        if ($request->has('action')) {
            $query->where('action', $request->string('action'));
        }
        if ($request->has('subject_type')) {
            $query->where('subject_type', $request->string('subject_type'));
        }

        $logs = $query->orderByDesc('created_at')->orderByDesc('id')->paginate($request->integer('per_page', 25));

        return $this->respondPaginated($logs, AuditLogResource::class);
    }
}
