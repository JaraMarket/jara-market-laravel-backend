<?php

namespace App\Http\Controllers\API;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Http\Requests\DecideOrderItemRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\IngredientOrderResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    public function __construct(public OrderService $orderService) {}

    // ── Customer ──────────────────────────────────────────────────────

    #[OA\Get(
        path: "/api/orders",
        summary: "Order History",
        description: "Retrieve a list of orders placed by the authenticated customer.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "per_page", in: "query", description: "Number of items per page", schema: new OA\Schema(type: "integer", default: 15))
        ],
        responses: [
            new OA\Response(response: 200, description: "Orders retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
    public function all(Request $request)
    {
        try {
            $orders = $this->orderService->all((int) $request->get('per_page', 15));

            return response()->success('Orders retrieved successfully',
                OrderResource::collection($orders)->response()->getData(true), 200);
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: "/api/orders/{order}",
        summary: "Order Details",
        description: "Retrieve detailed information about a specific order.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "order", in: "path", required: true, description: "The Order ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Order retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Order not found")
        ]
    )]
    public function show(Order $order)
    {
        try {
            $order = $this->orderService->getOrderById($order->id);

            return response()->success('Order retrieved successfully', new OrderResource($order), 200);
        } catch (GeneralException $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: "/api/orders",
        summary: "Place Order",
        description: "Create a new order with multiple products or ingredients.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["order_date", "delivery_type", "service_charge", "total"],
                properties: [
                    new OA\Property(property: "order_date", type: "string", format: "date", example: "2024-05-20"),
                    new OA\Property(property: "delivery_type", type: "string", enum: ["pickup", "delivery"], example: "delivery"),
                    new OA\Property(property: "shipping_fee", type: "number", format: "float", example: 500.00),
                    new OA\Property(property: "service_charge", type: "number", format: "float", example: 100.00),
                    new OA\Property(property: "vat", type: "number", format: "float", example: 50.00),
                    new OA\Property(property: "total", type: "number", format: "float", example: 5650.00),
                    new OA\Property(property: "remarks", type: "string", example: "Please deliver between 2pm and 4pm."),
                    new OA\Property(property: "address_id", type: "integer", example: 1),
                    new OA\Property(property: "products", type: "array", items: new OA\Items(
                        properties: [
                            new OA\Property(property: "product_id", type: "integer", example: 1),
                            new OA\Property(property: "quantity", type: "integer", example: 2),
                            new OA\Property(property: "price", type: "number", format: "float", example: 2500.00)
                        ]
                    )),
                    new OA\Property(property: "ingredients", type: "array", items: new OA\Items(
                        properties: [
                            new OA\Property(property: "ingredient_id", type: "integer", example: 5),
                            new OA\Property(property: "quantity", type: "integer", example: 3),
                            new OA\Property(property: "price", type: "number", format: "float", example: 1500.00),
                            new OA\Property(property: "unit", type: "string", example: "kg")
                        ]
                    ))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Order created successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(OrderRequest $request)
    {
        try {
            $order = $this->orderService->createOrder($request);

            return response()->success('Order created successfully', new OrderResource($order), 201);
        } catch (GeneralException $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], $e->getCode());
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Put(
        path: "/api/orders/{order}/cancel",
        summary: "Cancel Order",
        description: "Cancel a pending order.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "order", in: "path", required: true, description: "The Order ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Order cancelled successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Order not found")
        ]
    )]
    public function cancel(Order $order)
    {
        try {
            $order = $this->orderService->cancelOrder($order);

            return response()->success('Order cancelled successfully', new OrderResource($order), 200);
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ── Vendor ────────────────────────────────────────────────────────

    /** GET /vendor/orders — available (pending) items for this vendor */
    public function getAvailableOrders(Request $request)
    {
        try {
            $orders = $this->orderService->getAvailableOrders((int) $request->get('per_page', 20));

            return response()->success('Available orders retrieved successfully',
                IngredientOrderResource::collection($orders)->response()->getData(true), 200);
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** GET /vendor/orders/accepted — accepted orders assigned to this vendor */
    public function myOrders(Request $request)
    {
        try {
            $orders = $this->orderService->getMyOrders((int) $request->get('per_page', 20));

            return response()->success('Accepted orders retrieved successfully',
                IngredientOrderResource::collection($orders)->response()->getData(true), 200);
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** GET /vendor/orders/{id} — single order item detail */
    public function showOrderByItemId(int $item_id)
    {
        try {
            $orderItem = $this->orderService->showOrderByItemId($item_id);

            return response()->success('Order retrieved successfully', new IngredientOrderResource($orderItem), 200);
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** POST /vendor/orders/item/{id}/decision — accept or reject */
    public function decide(DecideOrderItemRequest $request, int $item_id)
    {
        try {
            $orderItem = $this->orderService->decide($request->validated(), $item_id);

            return response()->success('Action taken successfully', new IngredientOrderResource($orderItem), 200);
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
