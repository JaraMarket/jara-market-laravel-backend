<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Audits JaraMarket products for data integrity, pricing issues, and stock levels.')]
class AuditProductsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $limit = (int) $request->get('limit', 50);

        try {
            $products = Product::with(['categories', 'ingredients'])
                ->latest()
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return Response::text('Database Connection Error: '.$e->getMessage());
        }

        if ($products->isEmpty()) {
            return Response::text('No products found to audit.');
        }

        $auditResults = $products->map(function ($product) {
            $issues = [];

            if (empty($product->description)) {
                $issues[] = 'Missing description';
            }

            if (empty($product->image_url)) {
                $issues[] = 'Missing image URL';
            }

            if ($product->price <= 0) {
                $issues[] = 'Price is zero or negative';
            }

            if ($product->discount_price > 0 && $product->discount_price >= $product->price) {
                $issues[] = 'Discount price is higher than or equal to regular price';
            }

            if ($product->stock <= 0) {
                $issues[] = 'Out of stock';
            }

            if ($product->categories->isEmpty()) {
                $issues[] = 'No categories assigned';
            }

            if ($product->ingredients->isEmpty()) {
                $issues[] = 'No ingredients listed';
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'issues' => $issues,
                'status' => empty($issues) ? '✅ Pass' : '❌ Fail',
            ];
        });

        $failedCount = $auditResults->where('status', '❌ Fail')->count();
        $summary = "📋 JaraMarket Product Audit Summary\n";
        $summary .= "-----------------------------------\n";
        $summary .= 'Total Products Audited: '.$products->count()."\n";
        $summary .= 'Failed Checks: '.$failedCount."\n";
        $summary .= 'Passed Checks: '.($products->count() - $failedCount)."\n\n";

        foreach ($auditResults as $result) {
            $summary .= "[{$result['status']}] {$result['name']} (ID: {$result['id']})\n";
            foreach ($result['issues'] as $issue) {
                $summary .= "   - {$issue}\n";
            }
        }

        return Response::text($summary);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('The number of products to audit.')
                ->default(50),
        ];
    }
}
