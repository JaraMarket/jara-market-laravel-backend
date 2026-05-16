<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Models\Review;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | POST /api/vendors/{id}/reviews
    |--------------------------------------------------------------------------
    */
    #[OA\Post(
        path: "/api/vendors/{id}/reviews",
        summary: "Submit Review for Vendor",
        description: "Submit a rating and comment for a specific vendor.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Vendor ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["rating"],
                properties: [
                    new OA\Property(property: "rating", type: "integer", minimum: 1, maximum: 5, example: 5),
                    new OA\Property(property: "comment", type: "string", maxLength: 1000, example: "Excellent service and delicious food!")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Review submitted successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Vendor not found"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
    public function store(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        try {
            $vendor = User::vendors()->findOrFail($id);

            $review = Review::updateOrCreate(
                [
                    'vendor_id' => $vendor->id,
                    'user_id'   => $request->user()->id,
                ],
                [
                    'rating'  => $validated['rating'],
                    'comment' => $validated['comment'] ?? null,
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Review submitted successfully',
                'data'    => [
                    'id'      => $review->id,
                    'rating'  => $review->rating,
                    'comment' => $review->comment,
                ],
            ], 201);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to submit review: ' . $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/vendors/{id}/reviews
    |--------------------------------------------------------------------------
    */
    #[OA\Get(
        path: "/api/vendors/{id}/reviews",
        summary: "Get Vendor Reviews",
        description: "Retrieve all reviews for a specific vendor.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Vendor ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Reviews retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Vendor not found")
        ]
    )]
    public function index(int $id): JsonResponse
    {
        try {
            $vendor = User::vendors()->findOrFail($id);

            $reviews = Review::with('user:id,firstname,lastname,profile_picture')
                ->where('vendor_id', $vendor->id)
                ->latest()
                ->paginate(20);

            $avgRating = Review::where('vendor_id', $vendor->id)->avg('rating');

            return response()->json([
                'status'  => true,
                'message' => 'Reviews retrieved successfully',
                'data'    => [
                    'average_rating' => round((float) $avgRating, 1),
                    'total_reviews'  => $reviews->total(),
                    'reviews'        => $reviews->items(),
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/customers/{id}/reviews
    | Vendors reviewing customers
    |--------------------------------------------------------------------------
    */
    #[OA\Post(
        path: "/api/vendor/customers/{id}/reviews",
        summary: "Review a Customer",
        description: "Vendors can leave a review and rating for a customer after a transaction.",
        tags: ["Vendor"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Customer ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["rating"],
                properties: [
                    new OA\Property(property: "rating", type: "integer", minimum: 1, maximum: 5, example: 5),
                    new OA\Property(property: "comment", type: "string", example: "Very polite customer, quick payment.")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Review submitted successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Customer not found"),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
    public function storeCustomerReview(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        try {
            $customer = User::where('role', 'customer')->findOrFail($id);
            $vendorId = $request->user()->id;

            $review = Review::updateOrCreate(
                [
                    'vendor_id' => $vendorId,
                    'user_id'   => $customer->id,
                ],
                [
                    'rating'  => $validated['rating'],
                    'comment' => $validated['comment'] ?? null,
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Customer review submitted successfully',
                'data'    => [
                    'id'      => $review->id,
                    'rating'  => $review->rating,
                    'comment' => $review->comment,
                ],
            ], 201);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to submit review: ' . $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/customers/{id}/reviews
    | Get reviews for a customer
    |--------------------------------------------------------------------------
    */
    #[OA\Get(
        path: "/api/customers/{id}/reviews",
        summary: "Get Customer Reviews",
        description: "Retrieve all reviews received by a customer.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Customer ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Customer reviews retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Customer not found")
        ]
    )]
    public function indexCustomerReviews(int $id): JsonResponse
    {
        try {
            $customer = User::where('role', 'customer')->findOrFail($id);

            // Here we want to fetch the reviews where user_id = customer, but we need to know who wrote it (vendor)
            $reviews = Review::with('vendor:id,firstname,lastname,profile_picture')
                ->where('user_id', $customer->id)
                ->latest()
                ->paginate(20);

            $avgRating = Review::where('user_id', $customer->id)->avg('rating');

            return response()->json([
                'status'  => true,
                'message' => 'Customer reviews retrieved successfully',
                'data'    => [
                    'average_rating' => round((float) $avgRating, 1),
                    'total_reviews'  => $reviews->total(),
                    'reviews'        => $reviews->items(),
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Customer not found'], 404);
        }
    }
}
