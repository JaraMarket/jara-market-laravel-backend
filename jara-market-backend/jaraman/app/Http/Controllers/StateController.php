<?php

namespace App\Http\Controllers;

use App\Filters\StateFilter\Lga as StateFilterLga;
use App\Filters\StateFilter\Name as StateFilterName;
use App\Filters\StateFilter\Search as StateFilterSearch;
use App\Http\Resources\StatesAndLga\StateResource;
use App\Models\State;
use Illuminate\Http\Response;

class StateController extends Controller
{
    #[OA\Get(
        path: "/jaram/states",
        summary: "List States",
        description: "Retrieve all states with their LGAs.",
        tags: ["Catalogue"],
        responses: [
            new OA\Response(response: 200, description: "States retrieved successfully")
        ]
    )]
    public function index()
    {
        $states = State::with('lgas')->filterWithPipeline([
            StateFilterLga::class,
            StateFilterName::class,
            StateFilterSearch::class,
        ])->get();
        $states_collection = StateResource::collection($states);

        return response()->success('successful', $states_collection);
    }

    #[OA\Get(
        path: "/jaram/states/{state}",
        summary: "Get State Details",
        description: "Retrieve details of a specific state and its LGAs.",
        tags: ["Catalogue"],
        parameters: [
            new OA\Parameter(name: "state", in: "path", required: true, description: "The State ID or slug", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "State retrieved successfully"),
            new OA\Response(response: 404, description: "State not found")
        ]
    )]
    public function findState(State $state)
    {
        $state = $state->load('lgas');
        $state_collection = new StateResource($state);

        return response()->success('successful', $state_collection);
    }
}
