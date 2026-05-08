<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentLog;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AdminApiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /api/admin/users
    |--------------------------------------------------------------------------
    */
    public function users(Request $request): JsonResponse
    {
        try {
            $users = User::customers()
                ->when($request->search, function ($q) use ($request) {
                    $q->where(function ($q2) use ($request) {
                        $q2->where('firstname', 'like', "%{$request->search}%")
                           ->orWhere('lastname', 'like', "%{$request->search}%")
                           ->orWhere('email', 'like', "%{$request->search}%");
                    });
                })
                ->when($request->status, fn ($q) => $q->where('is_active', $request->status === 'active'))
                ->latest()
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Users retrieved successfully',
                'data'    => $users->items(),
                'meta'    => [
                    'total'        => $users->total(),
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to retrieve users'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/admin/users/{id}/suspend
    |--------------------------------------------------------------------------
    */
    public function suspendUser(int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->update(['is_active' => ! $user->is_active]);

            $action = $user->is_active ? 'activated' : 'suspended';

            return response()->json([
                'status'  => true,
                'message' => "User {$action} successfully",
                'data'    => ['id' => $user->id, 'is_active' => $user->is_active],
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/admin/vendors
    |--------------------------------------------------------------------------
    */
    public function vendors(Request $request): JsonResponse
    {
        try {
            $vendors = User::vendors()
                ->with(['state', 'categories', 'wallet'])
                ->when($request->search, function ($q) use ($request) {
                    $q->where(function ($q2) use ($request) {
                        $q2->where('firstname', 'like', "%{$request->search}%")
                           ->orWhere('business_name', 'like', "%{$request->search}%")
                           ->orWhere('email', 'like', "%{$request->search}%");
                    });
                })
                ->when($request->status, fn ($q) => $q->where('is_active', $request->status === 'active'))
                ->when($request->verified, fn ($q) => $q->where('is_verified', $request->verified === 'true'))
                ->latest()
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Vendors retrieved successfully',
                'data'    => $vendors->items(),
                'meta'    => [
                    'total'        => $vendors->total(),
                    'current_page' => $vendors->currentPage(),
                    'last_page'    => $vendors->lastPage(),
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to retrieve vendors'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/admin/vendors/{id}/approve
    |--------------------------------------------------------------------------
    */
    public function approveVendor(int $id): JsonResponse
    {
        try {
            $vendor = User::vendors()->findOrFail($id);
            $vendor->update(['is_verified' => true, 'is_active' => true]);

            return response()->json([
                'status'  => true,
                'message' => 'Vendor approved successfully',
                'data'    => ['id' => $vendor->id, 'is_verified' => true],
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/admin/vendors/{id}/reject
    |--------------------------------------------------------------------------
    */
    public function rejectVendor(int $id): JsonResponse
    {
        try {
            $vendor = User::vendors()->findOrFail($id);
            $vendor->update(['is_verified' => false, 'is_active' => false]);

            return response()->json([
                'status'  => true,
                'message' => 'Vendor rejected successfully',
                'data'    => ['id' => $vendor->id, 'is_verified' => false],
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/admin/orders
    |--------------------------------------------------------------------------
    */
    public function orders(Request $request): JsonResponse
    {
        try {
            $orders = Order::with(['user', 'items.ingredient', 'address'])
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->when($request->search, fn ($q) => $q->where('reference', 'like', "%{$request->search}%"))
                ->latest()
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Orders retrieved successfully',
                'data'    => $orders->items(),
                'meta'    => [
                    'total'        => $orders->total(),
                    'current_page' => $orders->currentPage(),
                    'last_page'    => $orders->lastPage(),
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to retrieve orders'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/admin/payments
    |--------------------------------------------------------------------------
    */
    public function payments(Request $request): JsonResponse
    {
        try {
            $payments = PaymentLog::with(['initiator', 'owner'])
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->latest()
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Payments retrieved successfully',
                'data'    => $payments->items(),
                'meta'    => [
                    'total'        => $payments->total(),
                    'current_page' => $payments->currentPage(),
                    'last_page'    => $payments->lastPage(),
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to retrieve payments'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/admin/categories
    |--------------------------------------------------------------------------
    */
    public function categories(Request $request): JsonResponse
    {
        try {
            $categories = Category::withCount(['products', 'ingredients'])
                ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->orderBy('name')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Categories retrieved successfully',
                'data'    => $categories,
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to retrieve categories'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/admin/categories
    |--------------------------------------------------------------------------
    */
    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:200|unique:categories,name',
            'description'      => 'nullable|string',
            'category_type_id' => 'nullable|integer|exists:category_types,id',
            'sort_by'          => 'nullable|integer',
        ]);

        try {
            $category = Category::create($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Category created successfully',
                'data'    => $category,
            ], 201);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to create category: ' . $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/admin/categories/{id}
    |--------------------------------------------------------------------------
    */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'sort_by'     => 'nullable|integer',
        ]);

        try {
            $category = Category::findOrFail($id);
            $category->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Category updated successfully',
                'data'    => $category,
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Category not found'], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/admin/categories/{id}
    |--------------------------------------------------------------------------
    */
    public function destroyCategory(int $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Category deleted successfully',
                'data'    => [],
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Category not found'], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/admin/notifications/send
    |--------------------------------------------------------------------------
    */
    public function sendNotification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'    => 'required|string|max:200',
            'message'  => 'required|string',
            'role'     => 'nullable|string|in:customer,vendor,all',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        try {
            $query = User::query()->where('is_active', true);

            if (! empty($validated['user_ids'])) {
                $query->whereIn('id', $validated['user_ids']);
            } elseif (! empty($validated['role']) && $validated['role'] !== 'all') {
                $query->where('role', $validated['role']);
            }

            $users = $query->whereNotNull('fcm_token')->get();
            $count = $users->count();

            // Store notification in DB for each user
            foreach ($users as $user) {
                $user->notify(new \App\Notifications\AdminBroadcastNotification(
                    $validated['title'],
                    $validated['message']
                ));
            }

            return response()->json([
                'status'  => true,
                'message' => "Notification sent to {$count} users",
                'data'    => ['recipients' => $count],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Notification dispatch failed: ' . $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/admin/dashboard/stats
    |--------------------------------------------------------------------------
    */
    public function dashboardStats(): JsonResponse
    {
        try {
            $stats = [
                'total_users'    => User::customers()->count(),
                'total_vendors'  => User::vendors()->count(),
                'active_vendors' => User::vendors()->where('is_active', true)->count(),
                'total_orders'   => Order::count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'total_revenue'  => OrderItem::where('status', 'completed')->sum('vendor_amount'),
                'orders_today'   => Order::whereDate('created_at', today())->count(),
                'revenue_today'  => OrderItem::where('status', 'completed')
                    ->whereDate('updated_at', today())
                    ->sum('vendor_amount'),
            ];

            return response()->json([
                'status'  => true,
                'message' => 'Dashboard stats retrieved successfully',
                'data'    => $stats,
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to retrieve stats'], 500);
        }
    }
}
