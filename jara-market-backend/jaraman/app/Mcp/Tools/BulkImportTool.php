<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use App\Models\Ingredient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Illuminate\Support\Facades\DB;

#[Description('Ultra-Capable Import: Populates products and synchronizes ingredients with full metadata support.')]
class BulkImportTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $filePath = $request->get('file_path');

        if (!file_exists($filePath)) {
            return Response::text("❌ File not found at: {$filePath}");
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $products = [];

        try {
            if (strtolower($extension) === 'json') {
                $products = json_decode(file_get_contents($filePath), true);
            } elseif (strtolower($extension) === 'csv') {
                if (($handle = fopen($filePath, "r")) !== FALSE) {
                    $header = fgetcsv($handle, 1000, ",");
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (count($header) === count($data)) {
                            $products[] = array_combine($header, $data);
                        }
                    }
                    fclose($handle);
                }
            } else {
                return Response::text("❌ Unsupported file format: {$extension}.");
            }
        } catch (\Exception $e) {
            return Response::text("❌ Error reading file: " . $e->getMessage());
        }

        $createdCount = 0;
        $errors = [];

        foreach ($products as $index => $data) {
            try {
                DB::transaction(function () use ($data, &$createdCount) {
                    $product = Product::create([
                        'name' => $data['name'],
                        'description' => $data['description'] ?? '',
                        'price' => $data['price'] ?? 0,
                        'discount_price' => $data['discount_price'] ?? 0,
                        'stock' => $data['stock'] ?? 0,
                        'image_url' => $data['image_url'] ?? null,
                        'preparation_steps' => $data['preparation_steps'] ?? null,
                        'rating' => $data['rating'] ?? 0,
                    ]);

                    // Handle Category IDs
                    if (!empty($data['category_ids'])) {
                        $ids = array_map('trim', explode(',', $data['category_ids']));
                        $product->categories()->sync($ids);
                    }

                    // --- Smart Ingredient Sync ---
                    if (!empty($data['ingredients'])) {
                        $ingredientsData = is_string($data['ingredients']) 
                            ? json_decode($data['ingredients'], true) 
                            : $data['ingredients'];
                        
                        if (is_array($ingredientsData)) {
                            foreach ($ingredientsData as $ing) {
                                // 1. Find or create the ingredient by name
                                $ingredient = null;
                                if (isset($ing['name'])) {
                                    $ingredient = Ingredient::firstOrCreate(
                                        ['name' => $ing['name']],
                                        [
                                            'description' => $ing['description'] ?? "Ingredient for {$product->name}",
                                            'price' => $ing['price'] ?? 0,
                                            'unit' => $ing['base_unit'] ?? 'piece',
                                            'stock' => $ing['stock'] ?? 1000,
                                        ]
                                    );
                                } elseif (isset($ing['id'])) {
                                    $ingredient = Ingredient::find($ing['id']);
                                }

                                // 2. Link to the product with pivot data
                                if ($ingredient) {
                                    $product->ingredients()->attach($ingredient->id, [
                                        'quantity' => $ing['quantity'] ?? 1,
                                        'unit' => $ing['unit'] ?? $ingredient->unit,
                                    ]);
                                }
                            }
                        }
                    }

                    $createdCount++;
                });
            } catch (\Exception $e) {
                $errors[] = "Line " . ($index + 1) . " [{$data['name']}]: " . $e->getMessage();
            }
        }

        $summary = "🚀 Ultra-Import Successful: {$createdCount} products imported with all ingredient fields synchronized.\n";
        if (!empty($errors)) {
            $summary .= "⚠️ Failed items:\n" . implode("\n", $errors);
        }

        return Response::text($summary);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'file_path' => $schema->string()->description('Absolute path to the data file.'),
        ];
    }
}
