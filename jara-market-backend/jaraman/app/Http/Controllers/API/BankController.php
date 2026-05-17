<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BankResource;
use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    #[OA\Get(
        path: "/api/banks",
        summary: "List Banks",
        description: "Retrieve a list of supported Nigerian banks for payouts.",
        tags: ["Customer", "Vendor"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", description: "Search by bank name", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Banks retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request)
    {
        $query = Bank::query();

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $banks = $query->orderBy('name')->get();

        return response()->success('Banks retrieved successfully', BankResource::collection($banks), 200);
    }
}
