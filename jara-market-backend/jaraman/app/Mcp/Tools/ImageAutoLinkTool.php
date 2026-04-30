<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Scans a directory for images and links them to products by matching filenames to product names.')]
class ImageAutoLinkTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $directory = $request->get('directory', 'public/products');
        $storageDisk = $request->get('disk', 'public');

        // Note: In local dev with Laragon, we assume images are accessible via public URL
        $files = Storage::disk($storageDisk)->files($directory);

        if (empty($files)) {
            return Response::text("⚠️ No files found in directory: {$directory} on disk: {$storageDisk}");
        }

        $linkedCount = 0;
        $summary = "🔍 Image Auto-Link Audit\n-----------------------\n";

        foreach ($files as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME); // e.g., 'jollof_rice'
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            // Try matching by exact name slugified
            $product = Product::where(function ($query) use ($filename) {
                $query->where('name', 'LIKE', str_replace(['_', '-'], ' ', $filename))
                    ->orWhere('name', 'LIKE', $filename);
            })->first();

            if ($product) {
                // Construct the public URL or path
                $url = Storage::disk($storageDisk)->url($file);
                $product->update(['image_url' => $url]);
                $summary .= "✅ Linked '{$file}' to Product: {$product->name}\n";
                $linkedCount++;
            } else {
                $summary .= "❓ No match for file: {$file}\n";
            }
        }

        $summary .= "\n🎯 Total Images Linked: {$linkedCount}";

        return Response::text($summary);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'directory' => $schema->string()->description('The directory to scan (relative to disk root).')->default('public/products'),
            'disk' => $schema->string()->description('The storage disk to use (default: public).')->default('public'),
        ];
    }
}
