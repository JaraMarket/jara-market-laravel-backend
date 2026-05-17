<?php

namespace App\Http\Controllers;

use App\Filters\CountryFilter\Name as CountryFilterName;
use App\Filters\CountryFilter\Search as CountryFilterSearch;
use App\Http\Resources\CountryResource;
use App\Http\Resources\StatesAndLga\StateResource;
use App\Models\Country;
use Exception;
use Illuminate\Http\Response;

class CountryController extends Controller
{
    #[OA\Get(
        path: "/jaram/country",
        summary: "List Countries",
        description: "Retrieve all supported countries.",
        tags: ["Catalogue"],
        responses: [
            new OA\Response(response: 200, description: "Countries retrieved successfully")
        ]
    )]
    public function index()
    {
        try {

            $country = Country::orderby('name', 'asc')->filterWithPipeline([
                CountryFilterName::class,
                CountryFilterSearch::class,
            ])->get();

            return response()->success('Countries retrieved successfully', CountryResource::collection($country));

        } catch (Exception $e) {
            report($e);

            return response()->errorResponse(['message' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: "/jaram/country/{c}/states",
        summary: "Get States by Country",
        description: "Retrieve all states belonging to a specific country.",
        tags: ["Catalogue"],
        parameters: [
            new OA\Parameter(name: "c", in: "path", required: true, description: "The Country ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "States retrieved successfully"),
            new OA\Response(response: 404, description: "Country not found")
        ]
    )]
    public function states($countryId)
    {
        try {

            $country = Country::with('states')->findOrFail($countryId);

            return response()->success('States retrieved successfully', StateResource::collection($country->states));

        } catch (Exception $e) {
            report($e);

            return response()->errorResponse(['message' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
