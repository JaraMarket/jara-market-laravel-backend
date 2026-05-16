<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Exception;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    /**
     * Panel endpoint — returns latest 15 notifications as JSON for the dropdown.
     * Called by the Alpine.js bell dropdown via fetch().
     */
    public function panel(Request $request)
    {
        try {
            $user = auth()->user();

            $notifications = $user->notifications()
                ->latest()
                ->take(15)
                ->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'type' => class_basename($n->type),
                    'title' => $n->data['type'] ?? 'Notification',
                    'message' => $n->data['message'] ?? null,
                    'is_read' => ! is_null($n->read_at),
                    'created_at' => $n->created_at->diffForHumans(),
                ]);

            return response()->json([
                'status' => true,
                'data' => $notifications,
                'unread' => $user->unreadNotifications()->count(),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'data' => [], 'unread' => 0], 500);
        }
    }

    #[OA\Get(
        path: "/api/notifications",
        summary: "Get Notifications",
        description: "Retrieve a paginated list of notifications for the authenticated user.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer", default: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Notifications retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $notifications = $user->notifications()
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'status'  => true,
                'message' => 'Notifications retrieved successfully',
                'data'    => NotificationResource::collection($notifications)
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch notifications'], 500);
        }
    }

    #[OA\Put(
        path: "/api/notifications/{id}/read",
        summary: "Mark Notification as Read",
        description: "Mark a specific notification as read.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Notification UUID", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Marked as read"),
            new OA\Response(response: 404, description: "Notification not found")
        ]
    )]
    public function markAsRead(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $notification = $user->notifications()->where('id', $id)->firstOrFail();
            $notification->markAsRead();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => true, 'message' => 'Marked as read']);
            }

            return back();
        } catch (Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => 'Notification not found'], 404);
            }

            return back();
        }
    }

    #[OA\Post(
        path: "/api/notifications/token",
        summary: "Update FCM Token",
        description: "Save the user's Firebase Cloud Messaging token for push notifications.",
        tags: ["Customer", "Vendor"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["fcm_token"],
                properties: [
                    new OA\Property(property: "fcm_token", type: "string", example: "fcm_token_123456")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Token updated successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['status' => true, 'message' => 'Token updated']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        try {
            auth()->user()->unreadNotifications->markAsRead();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => true, 'message' => 'All marked as read']);
            }

            return back()->with('success', 'All notifications marked as read.');
        } catch (Exception $e) {
            return back()->with('error', 'Something went wrong.');
        }
    }
}
