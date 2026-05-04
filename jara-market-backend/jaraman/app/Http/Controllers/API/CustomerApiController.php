<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CustomerApiController
 *
 * Handles public-facing customer endpoints:
 *   GET /api/vendors
 *   GET /api/vendors/{id}
 *   GET /api/categories
 *   GET /api/products
 *   GET /api/products/{id}
 */
class CustomerApiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /api/vendors
    | Supports: ?category={id}  ?search={keyword}
    |--------------------------------------------------------------------------
    */
    public function vendors(Request $request): JsonResponse
    {
        try {
            $vendors = User::vendors()
                ->with(['categories', 'state'])
                ->where('is_active', true)
                ->when($request->search, function ($q) use ($request) {
                    $q->where(function ($q2) use ($request) {
                        $q2->where('firstname', 'like', "%{$request->search}%")
                           ->orWhere('business_name', 'like', "%{$request->search}%");
                    });
                })
                ->when($request->category, function ($q) use ($request) {
                    $q->whereHas('categories', fn ($q2) => $q2->where('categories.id', $request->category));
                })
                ->latest()
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Vendors retrieved successfully',
                'data'    => array_map(fn ($v) => [
                    'id'            => $v->id,
                    'name'          => $v->name,
                    'business_name' => $v->business_name,
                    'profile_picture' => $v->profile_picture,
                    'state'         => $v->state?->name,
                    'categories'    => $v->categories->pluck('name'),
                    'is_verified'   => $v->is_verified ?? false,
                ], $vendors->items()),
                'meta' => [
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
    | GET /api/vendors/{id}
    |--------------------------------------------------------------------------
    */
    public function showVendor(int $id): JsonResponse
    {
        try {
            $vendor = User::vendors()
                ->with(['categories', 'state', 'lga'])
                ->where('is_active', true)
                ->findOrFail($id);

            return response()->json([
                'status'  => true,
                'message' => 'Vendor retrieved successfully',
                'data'    => [
                    'id'               => $vendor->id,
                    'name'             => $vendor->name,
                    'business_name'    => $vendor->business_name,
                    'business_address' => $vendor->business_address,
                    'profile_picture'  => $vendor->profile_picture,
                    'state'            => $vendor->state?->name,
                    'lga'              => $vendor->lga?->name,
                    'categories'       => $vendor->categories->pluck('name'),
                    'is_verified'      => $vendor->is_verified ?? false,
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/categories
    |--------------------------------------------------------------------------
    */
    public function categories(Request $request): JsonResponse
    {
        try {
            $categories = Category::withCount('products')
                ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->whereNull('deleted_at')
                ->orderBy('sort_by')
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
    | GET /api/products
    | Supports: ?vendor_id={id}  ?search={keyword}  ?category_id={id}
    |--------------------------------------------------------------------------
    */
    public function products(Request $request): JsonResponse
    {
        try {
            // Products are ingredients in the JaraMarket domain model
            $query = Ingredient::with(['category', 'statePrices'])
                ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
                ->when($request->vendor_id, function ($q) use ($request) {
                    // Filter ingredients belonging to a vendor's categories
                    $vendor      = User::find($request->vendor_id);
                    $categoryIds = $vendor?->categories()->pluck('categories.id') ?? [];
                    $q->whereIn('category_id', $categoryIds);
                })
                ->orderBy('name')
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Products retrieved successfully',
                'data'    => $query->items(),
                'meta'    => [
                    'total'        => $query->total(),
                    'current_page' => $query->currentPage(),
                    'last_page'    => $query->lastPage(),
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to retrieve products'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/products/{id}
    |--------------------------------------------------------------------------
    */
    public function showProduct(int $id): JsonResponse
    {
        try {
            $product = Ingredient::with(['category', 'statePrices', 'lgaPrices'])->findOrFail($id);

            return response()->json([
                'status'  => true,
                'message' => 'Product retrieved successfully',
                'data'    => $product,
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }
    }
}
