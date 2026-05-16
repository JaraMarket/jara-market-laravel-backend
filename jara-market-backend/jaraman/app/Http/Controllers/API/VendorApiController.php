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
use OpenApi\Attributes as OA;

class VendorApiController extends Controller
{
    #[OA\Get(
        path: "/api/vendor/profile",
        summary: "Get Vendor Profile",
        description: "Retrieve the authenticated vendor's profile, including business details and categories.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Profile retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
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

    #[OA\Put(
        path: "/api/vendor/profile",
        summary: "Update Vendor Profile",
        description: "Update vendor's business information.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "firstname", type: "string", example: "Jane"),
                    new OA\Property(property: "lastname", type: "string", example: "Smith"),
                    new OA\Property(property: "phone_number", type: "string", example: "+2348000000000"),
                    new OA\Property(property: "business_name", type: "string", example: "Fresh Mart"),
                    new OA\Property(property: "business_address", type: "string", example: "123 Market Road"),
                    new OA\Property(property: "state_id", type: "integer", example: 1),
                    new OA\Property(property: "lga_id", type: "integer", example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Profile updated successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
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

    #[OA\Post(
        path: "/api/vendor/upload-logo",
        summary: "Upload Vendor Logo",
        description: "Upload a logo for the vendor business.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["logo"],
                    properties: [
                        new OA\Property(property: "logo", type: "string", format: "binary", description: "Logo image (max 2MB)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Logo uploaded successfully"),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
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

    #[OA\Post(
        path: "/api/vendor/upload-banner",
        summary: "Upload Vendor Banner",
        description: "Upload a banner image for the vendor business.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["banner"],
                    properties: [
                        new OA\Property(property: "banner", type: "string", format: "binary", description: "Banner image (max 4MB)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Banner uploaded successfully"),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
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

    #[OA\Get(
        path: "/api/vendor/products",
        summary: "List Vendor Products (Ingredients)",
        description: "Retrieve a list of ingredients managed by the vendor.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", description: "Search by name", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer", default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: "Products retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
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

    #[OA\Post(
        path: "/api/vendor/products",
        summary: "Create Product",
        description: "Add a new ingredient/product to the vendor's catalog.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "price", "category_id"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Tomato"),
                    new OA\Property(property: "description", type: "string", example: "Fresh red tomatoes"),
                    new OA\Property(property: "price", type: "number", format: "float", example: 500.00),
                    new OA\Property(property: "category_id", type: "integer", example: 1),
                    new OA\Property(property: "stock", type: "integer", example: 100),
                    new OA\Property(property: "image_url", type: "string", format: "url", example: "https://example.com/tomato.jpg")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Product created successfully"),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
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

    #[OA\Get(
        path: "/api/vendor/products/{id}",
        summary: "Get Product Details",
        description: "Retrieve details of a specific ingredient.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Product ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Product retrieved successfully"),
            new OA\Response(response: 404, description: "Product not found")
        ]
    )]
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

    #[OA\Put(
        path: "/api/vendor/products/{id}",
        summary: "Update Product",
        description: "Update details of an existing product.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Product ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Red Tomato"),
                    new OA\Property(property: "price", type: "number", format: "float", example: 550.00),
                    new OA\Property(property: "stock", type: "integer", example: 150)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Product updated successfully"),
            new OA\Response(response: 404, description: "Product not found"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
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

    #[OA\Delete(
        path: "/api/vendor/products/{id}",
        summary: "Delete Product",
        description: "Remove a product from the vendor's catalog.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Product ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Product deleted successfully"),
            new OA\Response(response: 404, description: "Product not found")
        ]
    )]
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

    #[OA\Post(
        path: "/api/vendor/products/{id}/images",
        summary: "Upload Product Image",
        description: "Upload an image for a specific product.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Product ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["image"],
                    properties: [
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Product image (max 2MB)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Image uploaded successfully"),
            new OA\Response(response: 404, description: "Product not found"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
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

    #[OA\Get(
        path: "/api/vendor/orders",
        summary: "List Incoming Orders",
        description: "Retrieve a list of order items containing ingredients managed by the vendor.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "status", in: "query", description: "Filter by status (pending, accepted, rejected, processing, completed)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer", default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: "Orders retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
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

    #[OA\Get(
        path: "/api/vendor/orders/{id}",
        summary: "Get Order Details",
        description: "Retrieve details of a specific order item.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Order Item ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Order retrieved successfully"),
            new OA\Response(response: 404, description: "Order not found")
        ]
    )]
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

    #[OA\Put(
        path: "/api/vendor/orders/{id}/status",
        summary: "Update Order Status",
        description: "Accept, reject, or mark an order item as delivered/completed.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Order Item ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(property: "status", type: "string", enum: ["accepted", "rejected", "processing", "completed"], example: "accepted")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Order status updated successfully"),
            new OA\Response(response: 404, description: "Order not found"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
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

    #[OA\Get(
        path: "/api/vendor/earnings",
        summary: "View Earnings",
        description: "Retrieve vendor's total and monthly earnings, and wallet balance.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Earnings retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
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

    #[OA\Get(
        path: "/api/vendor/payouts",
        summary: "Payout History",
        description: "Retrieve history of bank transfers/payouts.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Payout history retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
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

    #[OA\Post(
        path: "/api/vendor/payouts/request",
        summary: "Request Payout",
        description: "Submit a request to transfer funds from vendor wallet to bank.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount", "bank_id"],
                properties: [
                    new OA\Property(property: "amount", type: "number", example: 5000.00),
                    new OA\Property(property: "bank_id", type: "integer", example: 1),
                    new OA\Property(property: "remark", type: "string", example: "Weekly payout")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Payout request submitted successfully"),
            new OA\Response(response: 422, description: "Insufficient balance or Validation error"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
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
