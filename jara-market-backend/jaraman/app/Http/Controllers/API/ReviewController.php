<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
