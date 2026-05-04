<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transfer;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorApiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /api/vendor/profile
    |--------------------------------------------------------------------------
    */
    public function profile(Request $request): JsonResponse
    {
        try {
            $vendor = $request->user()->load(['state', 'lga', 'categories']);

            return response()->json([
                'status'  => true,
                'message' => 'Vendor profile retrieved successfully',
                'data'    => [
                    'id'               => $vendor->id,
                    'firstname'        => $vendor->firstname,
                    'lastname'         => $vendor->lastname,
                    'email'            => $vendor->email,
                    'phone_number'     => $vendor->phone_number,
                    'business_name'    => $vendor->business_name,
                    'business_address' => $vendor->business_address,
                    'profile_picture'  => $vendor->profile_picture,
                    'state'            => $vendor->state?->name,
                    'lga'              => $vendor->lga?->name,
                    'categories'       => $vendor->categories->pluck('name'),
                    'is_verified'      => $vendor->is_verified ?? false,
                    'is_active'        => $vendor->is_active,
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to retrieve profile'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/vendor/profile
    |--------------------------------------------------------------------------
    */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firstname'        => 'sometimes|string|max:100',
            'lastname'         => 'sometimes|string|max:100',
            'phone_number'     => 'sometimes|string|max:20',
            'business_name'    => 'sometimes|string|max:200',
            'business_address' => 'sometimes|string|max:500',
            'state_id'         => 'sometimes|integer|exists:states,id',
            'lga_id'           => 'sometimes|integer|exists:lgas,id',
        ]);

        try {
            $vendor = $request->user();
            $vendor->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Profile updated successfully',
                'data'    => ['id' => $vendor->id, 'business_name' => $vendor->business_name],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to update profile'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/vendor/upload-logo
    |--------------------------------------------------------------------------
    */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            $path = Storage::disk('s3')->put('vendor/logos', $request->file('logo'), 'public');
            $url  = Storage::disk('s3')->url($path);

            $request->user()->update(['profile_picture' => $url]);

            return response()->json([
                'status'  => true,
                'message' => 'Logo uploaded successfully',
                'data'    => ['url' => $url],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Logo upload failed: ' . $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/vendor/upload-banner
    |--------------------------------------------------------------------------
    */
    public function uploadBanner(Request $request): JsonResponse
    {
        $request->validate([
            'banner' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        try {
            $path = Storage::disk('s3')->put('vendor/banners', $request->file('banner'), 'public');
            $url  = Storage::disk('s3')->url($path);

            // Store in a vendor meta field — extend User model if needed
            $request->user()->update(['business_address' => $request->user()->business_address]);
            // Save banner_url if column exists, otherwise just return the URL
            if (in_array('banner_url', $request->user()->getFillable())) {
                $request->user()->update(['banner_url' => $url]);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Banner uploaded successfully',
                'data'    => ['url' => $url],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Banner upload failed: ' . $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/vendor/products
    |--------------------------------------------------------------------------
    */
    public function products(Request $request): JsonResponse
    {
        try {
            $vendor = $request->user();

            // Vendors are linked to ingredients via categories
            $categoryIds = $vendor->categories()->pluck('categories.id');

            $ingredients = Ingredient::with(['category', 'statePrices', 'lgaPrices'])
                ->whereIn('category_id', $categoryIds)
                ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->orderBy('name')
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Vendor products retrieved successfully',
                'data'    => $ingredients->items(),
                'meta'    => [
                    'total'        => $ingredients->total(),
                    'per_page'     => $ingredients->perPage(),
                    'current_page' => $ingredients->currentPage(),
                    'last_page'    => $ingredients->lastPage(),
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to fetch products'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/vendor/products
    |--------------------------------------------------------------------------
    */
    public function storeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'category_id' => 'required|integer|exists:categories,id',
            'stock'       => 'nullable|integer|min:0',
            'image_url'   => 'nullable|url',
        ]);

        try {
            $ingredient = Ingredient::create(array_merge($validated, [
                'vendor_id' => $request->user()->id,
            ]));

            return response()->json([
                'status'  => true,
                'message' => 'Product created successfully',
                'data'    => $ingredient,
            ], 201);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to create product: ' . $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/vendor/products/{id}
    |--------------------------------------------------------------------------
    */
    public function showProduct(Request $request, int $id): JsonResponse
    {
        try {
            $vendor     = $request->user();
            $categoryIds = $vendor->categories()->pluck('categories.id');

            $ingredient = Ingredient::with(['category', 'statePrices'])
                ->whereIn('category_id', $categoryIds)
                ->findOrFail($id);

            return response()->json([
                'status'  => true,
                'message' => 'Product retrieved successfully',
                'data'    => $ingredient,
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/vendor/products/{id}
    |--------------------------------------------------------------------------
    */
    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'price'       => 'sometimes|numeric|min:0',
            'stock'       => 'nullable|integer|min:0',
        ]);

        try {
            $vendor      = $request->user();
            $categoryIds = $vendor->categories()->pluck('categories.id');

            $ingredient = Ingredient::whereIn('category_id', $categoryIds)->findOrFail($id);
            $ingredient->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Product updated successfully',
                'data'    => $ingredient,
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Product not found or update failed'], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/vendor/products/{id}
    |--------------------------------------------------------------------------
    */
    public function destroyProduct(Request $request, int $id): JsonResponse
    {
        try {
            $vendor      = $request->user();
            $categoryIds = $vendor->categories()->pluck('categories.id');

            $ingredient = Ingredient::whereIn('category_id', $categoryIds)->findOrFail($id);
            $ingredient->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Product deleted successfully',
                'data'    => [],
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/vendor/products/{id}/images
    |--------------------------------------------------------------------------
    */
    public function uploadProductImage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            $vendor      = $request->user();
            $categoryIds = $vendor->categories()->pluck('categories.id');

            $ingredient = Ingredient::whereIn('category_id', $categoryIds)->findOrFail($id);

            $path = Storage::disk('s3')->put('vendor/products', $request->file('image'), 'public');
            $url  = Storage::disk('s3')->url($path);

            $ingredient->update(['image_url' => $url]);

            return response()->json([
                'status'  => true,
                'message' => 'Product image uploaded successfully',
                'data'    => ['url' => $url],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Image upload failed: ' . $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/vendor/orders
    |--------------------------------------------------------------------------
    */
    public function orders(Request $request): JsonResponse
    {
        try {
            $vendor = $request->user();
            $categoryIds = $vendor->categories()->pluck('categories.id');

            $items = OrderItem::with(['order.user', 'order.address', 'ingredient'])
                ->whereHas('ingredient', fn ($q) => $q->whereIn('category_id', $categoryIds))
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->orderByDesc('created_at')
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Vendor orders retrieved successfully',
                'data'    => $items->items(),
                'meta'    => [
                    'total'        => $items->total(),
                    'current_page' => $items->currentPage(),
                    'last_page'    => $items->lastPage(),
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to fetch orders'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/vendor/orders/{id}
    |--------------------------------------------------------------------------
    */
    public function showOrder(Request $request, int $id): JsonResponse
    {
        try {
            $vendor      = $request->user();
            $categoryIds = $vendor->categories()->pluck('categories.id');

            $item = OrderItem::with(['order.user', 'order.address', 'order.items.ingredient', 'ingredient'])
                ->whereHas('ingredient', fn ($q) => $q->whereIn('category_id', $categoryIds))
                ->findOrFail($id);

            return response()->json([
                'status'  => true,
                'message' => 'Order retrieved successfully',
                'data'    => $item,
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Order not found'], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/vendor/orders/{id}/status
    |--------------------------------------------------------------------------
    */
    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:accepted,rejected,processing,completed',
        ]);

        try {
            $vendor      = $request->user();
            $categoryIds = $vendor->categories()->pluck('categories.id');

            $item = OrderItem::whereHas('ingredient', fn ($q) => $q->whereIn('category_id', $categoryIds))
                ->findOrFail($id);

            $item->update(['status' => $validated['status']]);

            return response()->json([
                'status'  => true,
                'message' => 'Order status updated successfully',
                'data'    => ['id' => $item->id, 'status' => $item->status],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to update order status'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/vendor/earnings
    |--------------------------------------------------------------------------
    */
    public function earnings(Request $request): JsonResponse
    {
        try {
            $vendor = $request->user();

            $total     = OrderItem::where('vendor_id', $vendor->id)->where('status', 'completed')->sum('vendor_amount');
            $thisMonth = OrderItem::where('vendor_id', $vendor->id)
                ->where('status', 'completed')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->sum('vendor_amount');

            $wallet = $vendor->wallet;

            return response()->json([
                'status'  => true,
                'message' => 'Earnings retrieved successfully',
                'data'    => [
                    'total_earned'        => round((float) $total, 2),
                    'earned_this_month'   => round((float) $thisMonth, 2),
                    'wallet_balance'      => $wallet ? round((float) $wallet->balance, 2) : 0.00,
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to retrieve earnings'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/vendor/payouts
    |--------------------------------------------------------------------------
    */
    public function payouts(Request $request): JsonResponse
    {
        try {
            $vendor   = $request->user();
            $transfers = Transfer::where(function ($q) use ($vendor) {
                $q->where('owner_id', $vendor->id)->where('owner_type', get_class($vendor));
            })
                ->orderByDesc('created_at')
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Payout history retrieved successfully',
                'data'    => $transfers->items(),
                'meta'    => [
                    'total'        => $transfers->total(),
                    'current_page' => $transfers->currentPage(),
                    'last_page'    => $transfers->lastPage(),
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to retrieve payouts'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/vendor/payouts/request
    | Delegates to WalletController::transferToBank logic
    |--------------------------------------------------------------------------
    */
    public function requestPayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'   => 'required|numeric|min:100',
            'bank_id'  => 'required|integer',
            'remark'   => 'nullable|string|max:200',
        ]);

        try {
            $vendor = $request->user();
            $wallet = $vendor->wallet;

            if (! $wallet || $wallet->balance < $validated['amount']) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Insufficient wallet balance for this payout',
                    'data'    => [],
                ], 422);
            }

            // Record the payout request
            $transfer = Transfer::create([
                'owner_id'   => $vendor->id,
                'owner_type' => get_class($vendor),
                'amount'     => $validated['amount'] * 100, // store in kobo
                'status'     => 'pending',
                'remark'     => $validated['remark'] ?? 'Payout request',
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Payout request submitted successfully. Processing within 24 hours.',
                'data'    => ['transfer_id' => $transfer->id, 'amount' => $validated['amount']],
            ], 201);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Payout request failed: ' . $e->getMessage()], 500);
        }
    }
}
