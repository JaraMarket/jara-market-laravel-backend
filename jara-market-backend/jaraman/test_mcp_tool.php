<?php

use App\Mcp\Tools\CreateProductTool;
use Laravel\Mcp\Request;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = new Request([
    'name' => 'Gourmet Egusi Soup',
    'description' => 'A rich, authentic Egusi soup made with melon seeds, spinach, and premium proteins.',
    'price' => 6500.00,
    'discount_price' => 6000.00,
    'stock' => 25,
    'image_url' => 'http://localhost:8000/storage/products/egusi.jpg',
    'preparation_steps' => '1. Blend egusi. 2. Boil meat. 3. Fry palm oil and onions. 4. Add egusi and simmer. 5. Add veggies.',
    'rating' => 4.9,
    'category_ids' => [2, 3],
    'ingredients' => [
        ['id' => 1, 'quantity' => 2, 'unit' => 'piece'],
        ['id' => 2, 'quantity' => 500, 'unit' => 'g']
    ],
    'state_prices' => [
        ['state_id' => 4, 'price' => 5500.00, 'discount_price' => 5000.00]
    ]
]);

$tool = new CreateProductTool();
$response = $tool->handle($request);
echo (string) $response->content();
