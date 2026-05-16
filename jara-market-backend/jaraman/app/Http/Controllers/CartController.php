<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class CartController extends Controller
{
    #[OA\Get(
        path: "/api/cart",
        summary: "View Cart",
        description: "Retrieve the authenticated user's cart items.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Cart retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index()
    {
        $user = Auth::user();
        $carts = Cart::where('user_id', $user->id)
            ->with('items.product')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $carts,
        ]);
    }

    #[OA\Post(
        path: "/api/cart",
        summary: "Add to Cart",
        description: "Add a product to the user's cart. If the product already exists, the quantity is updated.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["product_id", "quantity"],
                properties: [
                    new OA\Property(property: "product_id", type: "integer", example: 1),
                    new OA\Property(property: "quantity", type: "integer", minimum: 1, example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Product added to cart successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        // Get or create cart for the user
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['total_amount' => 0]
        );

        $product = Product::findOrFail($request->product_id);

        // Check if product already in cart
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $request->quantity,
                'price' => $product->price * ($existingItem->quantity + $request->quantity == 0 ? 1 : $existingItem->quantity + $request->quantity),
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'price' => $product->price * ($request->quantity == 0 ? 1 : $request->quantity),
            ]);
        }

        // Update cart total
        $this->updateCartTotal($cart);

        return response()->json([
            'status' => 'success',
            'message' => 'Product added to cart successfully',
            'data' => $cart->load('items.product'),
        ], 201);
    }

    #[OA\Get(
        path: "/api/cart/{id}",
        summary: "Get Cart Details",
        description: "Retrieve details of a specific cart (usually only one active cart exists per user).",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Cart ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Cart retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Cart not found")
        ]
    )]
    public function show(string $id)
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)
            ->with('items.product')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $cart,
        ]);
    }

    #[OA\Put(
        path: "/api/cart/{id}",
        summary: "Update Cart Item",
        description: "Update the quantity of a specific item in the cart.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Cart ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["item_id", "quantity"],
                properties: [
                    new OA\Property(property: "item_id", type: "integer", example: 10, description: "The ID of the CartItem record"),
                    new OA\Property(property: "quantity", type: "integer", minimum: 1, example: 5)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Cart item updated successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Cart or Item not found")
        ]
    )]
    public function update(Request $request, string $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)
            ->findOrFail($id);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $request->item_id)
            ->firstOrFail();

        $product = Product::findOrFail($cartItem->product_id);

        $cartItem->update([
            'quantity' => $request->quantity,
            'price' => $product->price * $request->quantity == 0 ? 1 : $product->price * $request->quantity,
        ]);

        // Update cart total
        $this->updateCartTotal($cart);

        return response()->json([
            'status' => 'success',
            'message' => 'Cart item updated successfully',
            'data' => $cart->load('items.product'),
        ]);
    }

    #[OA\Delete(
        path: "/api/cart/{id}",
        summary: "Remove Cart Item",
        description: "Remove a specific item from the cart.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "The Cart ID", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "item_id", in: "query", required: true, description: "The ID of the CartItem record", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Cart item removed successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Cart or Item not found")
        ]
    )]
    public function destroy(Request $request, string $id)
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)
            ->findOrFail($id);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $request->item_id)
            ->firstOrFail();

        $cartItem->delete();

        // Update cart total
        $this->updateCartTotal($cart);

        return response()->json([
            'status' => 'success',
            'message' => 'Cart item removed successfully',
        ]);
    }

    /**
     * Helper method to update cart total
     */
    private function updateCartTotal($cart)
    {
        $total = CartItem::where('cart_id', $cart->id)
            ->sum('price');

        $cart->update(['total_amount' => $total]);
    }
}
