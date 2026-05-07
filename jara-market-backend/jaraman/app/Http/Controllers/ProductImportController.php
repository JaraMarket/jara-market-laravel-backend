<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Step;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ProductImportController extends Controller
{
    /**
     * Download the CSV Template
     */
    public function downloadTemplate()
    {
        $headers = [
            'Name', 'Category', 'Price', 'Discount Price', 'Stock', 'Rating (1-5)',
            'Description', 'Image URL', 'Ingredients (Name:Qty:Unit, Name:Qty:Unit)', 
            'Preparation Steps (Step 1 | Step 2 | Step 3)'
        ];

        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            // Sample Row
            fputcsv($file, [
                'Jollof Rice Special', 'Meals', '2500', '2200', '50', '4.5', 
                'Delicious party jollof rice.', 'https://example.com/jollof.jpg', 
                'Rice:1:kg, Pepper:5:pcs, Oil:100:ml', 
                'Wash the rice | Prepare the tomato base | Cook until tender'
            ]);

            // Add a separator for instructions
            fputcsv($file, []);
            fputcsv($file, ['--- INSTRUCTIONS & VALID CATEGORIES ---']);
            
            // Fetch and list valid categories for the vendor
            $validCategories = Category::pluck('name')->toArray();
            fputcsv($file, ['IMPORTANT: Use only these exact categories: ' . implode(', ', $validCategories)]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=jaramarket_product_template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ]);
    }

    /**
     * Process the Uploaded CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('csv_file');
        $data = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($data);

        $totalRows = count($data);
        $imported = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($data as $index => $row) {
                // Skip empty rows or instruction rows
                if (empty($row) || count($row) < 3 || $row[0] === '--- INSTRUCTIONS & VALID CATEGORIES ---') continue;

                $rowData = array_combine($header, $row);

                // 1. Prepare Steps Data for the text field
                $stepsInput = $rowData['Preparation Steps (Step 1 | Step 2 | Step 3)'] ?? '';
                $stepsArray = !empty($stepsInput) ? explode('|', $stepsInput) : [];
                $formattedSteps = "";
                foreach($stepsArray as $idx => $s) {
                    $formattedSteps .= ($idx + 1) . ". " . trim($s) . "\n";
                }

                // 2. Create Product
                $product = Product::create([
                    'name'              => $rowData['Name'],
                    'description'       => $rowData['Description'] ?? null,
                    'price'             => $rowData['Price'],
                    'discount_price'    => $rowData['Discount Price'] ?? null,
                    'stock'             => $rowData['Stock'] ?? 0,
                    'rating'            => $rowData['Rating (1-5)'] ?? 0,
                    'image_url'         => $rowData['Image URL'] ?? null,
                    'preparation_steps' => trim($formattedSteps), // FIX: Populating the text field
                ]);

                // 3. Handle Categories (RESTRICTED TO EXISTING)
                if (!empty($rowData['Category'])) {
                    $categoryNames = explode(',', $rowData['Category']);
                    foreach ($categoryNames as $catName) {
                        $category = Category::where('name', 'like', trim($catName))->first();
                        if ($category) {
                            $product->categories()->attach($category->id);
                        } else {
                            $errors[] = "Row " . ($index + 2) . ": Category '" . trim($catName) . "' not found. Skipped category link.";
                        }
                    }
                }

                // 4. Handle Ingredients
                if (!empty($rowData['Ingredients (Name:Qty:Unit, Name:Qty:Unit)'])) {
                    $ingredients = explode(',', $rowData['Ingredients (Name:Qty:Unit, Name:Qty:Unit)']);
                    foreach ($ingredients as $ingData) {
                        $parts = explode(':', trim($ingData));
                        if (count($parts) >= 1) {
                            $ingName = $parts[0];
                            $qty = $parts[1] ?? 1;
                            $unit = $parts[2] ?? 'pcs';

                            $ingredient = Ingredient::firstOrCreate(['name' => $ingName], [
                                'unit' => $unit,
                                'price' => 0,
                                'stock' => 100
                            ]);

                            $product->ingredients()->attach($ingredient->id, [
                                'quantity' => $qty,
                                'unit' => $unit
                            ]);
                        }
                    }
                }

                // 5. Handle Steps Relationship
                foreach ($stepsArray as $stepDesc) {
                    $product->steps()->create([
                        'description' => trim($stepDesc),
                    ]);
                }

                $imported++;
            }

            DB::commit();
            
            $msg = "Successfully imported $imported products!";
            if (!empty($errors)) $msg .= " (Note: " . count($errors) . " category warnings)";

            return response()->json([
                'success' => true,
                'message' => $msg,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
