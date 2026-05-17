<?php

namespace App\Http\Controllers;

use App\Filters\LgaFilter\Name as LgaFilterByName;
use App\Filters\LgaFilter\Search as LgaFilterSearch;
use App\Filters\LgaFilter\State as LgaFilterState;
use App\Http\Resources\StatesAndLga\LgaResource;
use App\Models\Lga;
use Illuminate\Http\Response;

class LgaController extends Controller
{
    #[OA\Get(
        path: "/jaram/lgas",
        summary: "List LGAs",
        description: "Retrieve all Local Government Areas.",
        tags: ["Catalogue"],
        responses: [
            new OA\Response(response: 200, description: "LGAs retrieved successfully")
        ]
    )]
    public function index()
    {
        $lgas = Lga::with('state')->filterWithPipeline([
            LgaFilterState::class,
            LgaFilterByName::class,
            LgaFilterSearch::class,
        ])->get();
        $lga_collection = LgaResource::collection($lgas);

        return response()->success('successful', $lga_collection);
    }

    #[OA\Get(
        path: "/jaram/lgas/{lga}",
        summary: "Get LGA Details",
        description: "Retrieve details of a specific LGA.",
        tags: ["Catalogue"],
        parameters: [
            new OA\Parameter(name: "lga", in: "path", required: true, description: "The LGA ID or slug", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "LGA retrieved successfully"),
            new OA\Response(response: 404, description: "LGA not found")
        ]
    )]
    public function findLga(Lga $lga)
    {
        $state = $lga->load('state');
        $lga_collection = new LgaResource($state);

        return response()->success('successful', $lga_collection);
    }
}
