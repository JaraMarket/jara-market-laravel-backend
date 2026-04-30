<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Bulk populates JaraMarket with products. Useful for adding many entries at once.')]
class PopulateProductsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $productsData = $request->get('products', []);

        if (empty($productsData)) {
            return Response::text('No product data provided.');
        }

        $createdCount = 0;
        $errors = [];

        foreach ($productsData as $data) {
            try {
                // Simplified creation - add logic for categories/ingredients if needed
                Product::create([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? '',
                    'price' => $data['price'],
                    'discount_price' => $data['discount_price'] ?? 0,
                    'stock' => $data['stock'] ?? 10,
                    'image_url' => $data['image_url'] ?? null,
                    'status' => 'active',
                ]);
                $createdCount++;
            } catch (\Exception $e) {
                $errors[] = "Error adding [{$data['name']}]: ".$e->getMessage();
            }
        }

        $summary = "✅ Successfully added {$createdCount} products.\n";
        if (! empty($errors)) {
            $summary .= '❌ Failed to add '.count($errors)." products:\n";
            $summary .= implode("\n", $errors);
        }

        return Response::text($summary);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'products' => $schema->array()
                ->items(
                    $schema->object()->properties([
                        'name' => $schema->string()->description('The name of the product.'),
                        'description' => $schema->string()->description('The product description.'),
                        'price' => $schema->number()->description('The regular price.'),
                        'discount_price' => $schema->number()->description('The discount price (optional).'),
                        'stock' => $schema->integer()->description('The initial stock level.'),
                        'image_url' => $schema->string()->description('URL to the product image.'),
                    ])
                )
                ->description('An array of product objects to create.'),
        ];
    }
}
