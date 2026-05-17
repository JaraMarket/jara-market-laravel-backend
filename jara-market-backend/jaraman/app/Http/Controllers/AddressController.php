<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Exception;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    #[OA\Get(
        path: "/api/addresses",
        summary: "List Addresses",
        description: "Retrieve all saved delivery addresses for the authenticated user.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Addresses retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index()
    {
        try {
            $addresses = auth()->user()->addresses()->latest()->get();

            return response()->success('Addresses retrieved successfully', AddressResource::collection($addresses));
        } catch (Exception $e) {
            return response()->errorResponse('Failed to fetch addresses', [], 500);
        }
    }

    #[OA\Post(
        path: "/api/addresses",
        summary: "Create Address",
        description: "Save a new delivery address.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["address", "state_id", "lga_id"],
                properties: [
                    new OA\Property(property: "address", type: "string", example: "123 Main St, Ikeja"),
                    new OA\Property(property: "state_id", type: "integer", example: 1),
                    new OA\Property(property: "lga_id", type: "integer", example: 1),
                    new OA\Property(property: "is_default", type: "boolean", example: true),
                    new OA\Property(property: "label", type: "string", example: "Home")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Address created successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(AddressRequest $request)
    {
        try {
            $address = DB::transaction(function () use ($request) {
                $user = auth()->user();

                // If user marks this new address as default, reset all others
                if ($request->is_default) {
                    $user->addresses()->update(['is_default' => false]);
                }

                return $user->addresses()->create($request->validated());
            });

            return response()->success('Address created successfully', new AddressResource($address));
        } catch (Exception $e) {
            return response()->errorResponse($e->getMessage(), [], $e->getCode());
        }
    }

    #[OA\Put(
        path: "/api/addresses/{address}",
        summary: "Update Address",
        description: "Update an existing delivery address.",
        tags: ["Customer"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "address", in: "path", required: true, description: "The Address ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "address", type: "string", example: "456 Updated St"),
                    new OA\Property(property: "is_default", type: "boolean", example: false),
                    new OA\Property(property: "label", type: "string", example: "Office")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Address updated successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Address not found")
        ]
    )]
    public function update(AddressRequest $request, Address $address)
    {
        try {
            if ($address->user_id !== auth()->id()) {
                return response()->errorResponse('Unauthorized access to address', [], 403);
            }

            DB::transaction(function () use ($request, $address) {
                if ($request->is_default) {
                    $address->user->addresses()->update(['is_default' => false]);
                }

                $address->update($request->validated());
            });

            return response()->success('Address updated successfully', new AddressResource($address));
        } catch (Exception $e) {
            return response()->errorResponse($e->getMessage(), [], $e->getCode());
        }
    }
}
