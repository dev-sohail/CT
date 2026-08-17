<?php

namespace App\Domains\NotificationHubAndEventBus\Http\Controllers;

use App\Domains\NotificationHubAndEventBus\Http\Resources\NotificationResource;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return $this->respondPaginated($notifications, NotificationResource::class);
    }

    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();

        return $this->respondSuccess(['count' => $count]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->respondSuccess(NotificationResource::make($notification)->toArray($request));
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->respondNoContent();
    }

    public function destroy(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->delete();

        return $this->respondNoContent();
    }
}
