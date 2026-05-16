<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Jaramarket API",
    version: "1.0.0",
    description: "Jaramarket Meals Marketplace API for Customer and Vendor mobile applications"
)]
#[OA\Server(
    url: "L5_SWAGGER_CONST_HOST",
    description: "API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Enter token in format (Bearer <token>)"
)]
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
