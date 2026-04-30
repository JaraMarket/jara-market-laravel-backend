<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Creates a single product with full details, including categories and ingredients.')]
class CreateProductTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        return DB::transaction(function () use ($request) {
            try {
                $product = Product::create([
                    'name' => $request->get('name'),
                    'description' => $request->get('description', ''),
                    'price' => $request->get('price'),
                    'discount_price' => $request->get('discount_price', 0),
                    'stock' => $request->get('stock', 0),
                    'image_url' => $request->get('image_url'),
                    'preparation_steps' => $request->get('preparation_steps'),
                    'rating' => $request->get('rating', 0),
                ]);

                // Attach Categories if provided
                if ($categoryIds = $request->get('category_ids')) {
                    $product->categories()->sync($categoryIds);
                }

                // Attach Ingredients if provided
                if ($ingredients = $request->get('ingredients')) {
                    foreach ($ingredients as $ingredient) {
                        $product->ingredients()->attach($ingredient['id'], [
                            'quantity' => $ingredient['quantity'],
                            'unit' => $ingredient['unit'],
                        ]);
                    }
                }

                // Create State Prices if provided
                if ($statePrices = $request->get('state_prices')) {
                    foreach ($statePrices as $sp) {
                        $product->statePrices()->create([
                            'state_id' => $sp['state_id'],
                            'price' => $sp['price'],
                            'discount_price' => $sp['discount_price'] ?? 0,
                        ]);
                    }
                }

                return Response::text("✅ Product '{$product->name}' (ID: {$product->id}) created successfully with ALL variables.");
            } catch (\Exception $e) {
                return Response::text('❌ Failed to create product: '.$e->getMessage());
            }
        });
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The name of the product.'),
            'description' => $schema->string()->description('Product description.'),
            'price' => $schema->number()->description('Regular price.'),
            'discount_price' => $schema->number()->description('Discounted price (default 0).'),
            'stock' => $schema->integer()->description('Stock quantity.'),
            'image_url' => $schema->string()->description('URL to the product image.'),
            'preparation_steps' => $schema->string()->description('Text describing how to prepare the item.'),
            'rating' => $schema->number()->description('Initial rating (0-5).'),
            'category_ids' => $schema->array()->items($schema->integer())->description('List of category IDs to assign.'),
            'ingredients' => $schema->array()->items(
                $schema->object()->properties([
                    'id' => $schema->integer()->description('The ID of the ingredient.'),
                    'quantity' => $schema->number()->description('Amount of the ingredient.'),
                    'unit' => $schema->string()->description('Unit of measurement (e.g., kg, piece).'),
                ])
            )->description('List of ingredients with quantities and units.'),
            'state_prices' => $schema->array()->items(
                $schema->object()->properties([
                    'state_id' => $schema->integer()->description('The ID of the state.'),
                    'price' => $schema->number()->description('Override price for this state.'),
                    'discount_price' => $schema->number()->description('Override discount price for this state.'),
                ])
            )->description('Regional price overrides for specific states.'),
        ];
    }
}
