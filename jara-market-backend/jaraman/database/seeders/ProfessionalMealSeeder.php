<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfessionalMealSeeder extends Seeder
{
    public function run(): void
    {
        $meals = [
            [
                'name' => 'Gourmet Egusi Soup (Traditional Style)',
                'description' => 'A rich, hearty Nigerian classic prepared with finely ground melon seeds, spinach, and a selection of premium beef. Perfect for pairing with Pounded Yam or Eba.',
                'price' => 7500.00,
                'discount_price' => 6800.00,
                'stock' => 50,
                'image_url' => 'https://jara-market.s3.amazonaws.com/products/egusi-soup.jpg',
                'preparation_steps' => "1. Season and boil beef until tender.\n2. Blend Egusi with onions to form a paste.\n3. Fry Egusi in palm oil for 5-8 mins.\n4. Add stock and simmer for 10 mins.\n5. Add vegetables and seasoning. Cook for 3 mins.",
                'rating' => 4.8,
                'categories' => [2, 3], 
                'ingredients' => [['id' => 1, 'quantity' => 0.5, 'unit' => 'kg']],
            ],
            [
                'name' => 'Smoky Jollof Rice (Party Style)',
                'description' => 'Infused with the authentic "party" smokiness, our Jollof Rice is slow-cooked with fresh tomatoes, bell peppers, and traditional spices for a perfect flavor profile.',
                'price' => 4500.00,
                'discount_price' => 4000.00,
                'stock' => 100,
                'image_url' => 'https://jara-market.s3.amazonaws.com/products/jollof-rice.jpg',
                'preparation_steps' => "1. Parboil rice and drain.\n2. Blend and fry tomato/pepper base until dry.\n3. Add stock and spices, bring to boil.\n4. Add rice, cover with foil, and steam on low heat.\n5. Stir in butter and onions at the end for aroma.",
                'rating' => 4.9,
                'categories' => [1],
                'ingredients' => [['id' => 2, 'quantity' => 1, 'unit' => 'kg']],
            ],
            [
                'name' => 'Classic Edikaikong Soup',
                'description' => 'A nutrient-dense vegetable soup from Cross River State, made with fresh Waterleaf and Ugu (Pumpkin leaves), periwinkles, and assorted meats.',
                'price' => 8500.00,
                'discount_price' => 8000.00,
                'stock' => 30,
                'image_url' => 'https://jara-market.s3.amazonaws.com/products/edikaikong.jpg',
                'preparation_steps' => "1. Boil assorted meats with stockfish.\n2. Add palm oil, crayfish, and pepper.\n3. Add periwinkles and cook for 5 mins.\n4. Add Waterleaf first, cook for 3 mins.\n5. Add Ugu leaves, stir, and simmer for 2 mins.",
                'rating' => 4.7,
                'categories' => [2, 3],
                'ingredients' => [['id' => 1, 'quantity' => 1, 'unit' => 'kg']],
            ],
            [
                'name' => 'Assorted Meat Pepper Soup',
                'description' => 'Light yet intensely flavorful, our pepper soup is a blend of aromatic Nigerian spices, ginger, and garlic, featuring tender cuts of cow tripe and beef.',
                'price' => 5500.00,
                'discount_price' => 0,
                'stock' => 40,
                'image_url' => 'https://jara-market.s3.amazonaws.com/products/pepper-soup.jpg',
                'preparation_steps' => "1. Wash and boil assorted meats.\n2. Add pepper soup spice mix, ginger, and garlic.\n3. Add dry pepper and scent leaves.\n4. Simmer on medium heat until meat is very tender.\n5. Adjust salt and serve hot.",
                'rating' => 4.6,
                'categories' => [2],
                'ingredients' => [['id' => 2, 'quantity' => 0.5, 'unit' => 'kg']],
            ],
            [
                'name' => 'Yam Porridge with Garden Eggs',
                'description' => 'Tender yam cubes cooked in a savory palm oil sauce with mashed garden eggs and smoked fish for an earthy, traditional taste.',
                'price' => 3800.00,
                'discount_price' => 3500.00,
                'stock' => 25,
                'image_url' => 'https://jara-market.s3.amazonaws.com/products/yam-porridge.jpg',
                'preparation_steps' => "1. Peel and cube yam, boil with water.\n2. Add mashed garden eggs and palm oil.\n3. Stir in smoked fish and crayfish.\n4. Cook until yam is soft and sauce is thick.\n5. Garnish with chopped greens.",
                'rating' => 4.5,
                'categories' => [5],
                'ingredients' => [['id' => 4, 'quantity' => 1, 'unit' => 'piece']],
            ],
            [
                'name' => 'Native Rice with Dried Fish',
                'description' => 'A rustic, one-pot rice dish made with local palm oil, locust beans (iru), and chunks of dried fish for a deeply nostalgic flavor.',
                'price' => 4200.00,
                'discount_price' => 0,
                'stock' => 60,
                'image_url' => 'https://jara-market.s3.amazonaws.com/products/native-rice.jpg',
                'preparation_steps' => "1. Heat palm oil and fry locust beans and onions.\n2. Add blended pepper and dried fish.\n3. Add water/stock and parboiled rice.\n4. Cover and cook until rice absorbs all liquid.\n5. Stir in scent leaves before serving.",
                'rating' => 4.7,
                'categories' => [1, 4],
                'ingredients' => [['id' => 2, 'quantity' => 1, 'unit' => 'kg']],
            ],
            [
                'name' => 'Vegetable Fried Rice',
                'description' => 'A vibrant mix of basmati rice, colorful vegetables (carrots, peas, sweet corn), and light soy sauce for a healthy and satisfying meal.',
                'price' => 4800.00,
                'discount_price' => 4500.00,
                'stock' => 80,
                'image_url' => 'https://jara-market.s3.amazonaws.com/products/fried-rice.jpg',
                'preparation_steps' => "1. Cook rice with curry and stock until 90% done.\n2. Sauté chopped carrots, peas, and corn in butter.\n3. Add rice to the veggies and stir-fry.\n4. Season with white pepper and seasoning cubes.\n5. Serve with a side of slaw.",
                'rating' => 4.8,
                'categories' => [1, 3],
                'ingredients' => [['id' => 4, 'quantity' => 1, 'unit' => 'kg']],
            ],
            [
                'name' => 'Beans & Fried Plantain Duo',
                'description' => 'Soft-cooked honey beans served with sweet, caramelized dodo (fried plantain) and a signature spicy pepper sauce.',
                'price' => 3200.00,
                'discount_price' => 3000.00,
                'stock' => 120,
                'image_url' => 'https://jara-market.s3.amazonaws.com/products/beans-plantain.jpg',
                'preparation_steps' => "1. Boil beans until very soft.\n2. Prepare a separate sauce with palm oil and onions.\n3. Mix sauce into the beans and simmer.\n4. Slice plantains and deep fry until golden brown.\n5. Plate beans with plantains on the side.",
                'rating' => 4.4,
                'categories' => [4, 5],
                'ingredients' => [['id' => 2, 'quantity' => 0.5, 'unit' => 'kg']],
            ],
            [
                'name' => 'Afang Soup (Calabar Special)',
                'description' => 'Authentically prepared with shredded Afang leaves and Waterleaf, rich in periwinkles and cow skin (kanda) for a unique texture.',
                'price' => 8200.00,
                'discount_price' => 7800.00,
                'stock' => 35,
                'image_url' => 'https://jara-market.s3.amazonaws.com/products/afang-soup.jpg',
                'preparation_steps' => "1. Boil beef and kanda with salt and onions.\n2. Add palm oil, crayfish, and blended pepper.\n3. Stir in Waterleaf and cook for 3 mins.\n4. Add ground Afang leaves and stir vigorously.\n5. Remove from heat immediately to preserve color.",
                'rating' => 4.9,
                'categories' => [2, 3],
                'ingredients' => [['id' => 1, 'quantity' => 1, 'unit' => 'kg']],
            ],
            [
                'name' => 'White Soup (Ofe-Nsala) with Catfish',
                'description' => 'A spicy, yam-thickened clear soup typical of the Igbo people, featuring fresh catfish and the aromatic \"Utazi\" leaves.',
                'price' => 9500.00,
                'discount_price' => 9000.00,
                'stock' => 20,
                'image_url' => 'https://jara-market.s3.amazonaws.com/products/white-soup.jpg',
                'preparation_steps' => "1. Boil yam slices and mash into a paste.\n2. Clean catfish with hot water to remove slime.\n3. Boil fish with peppers, crayfish, and Oda spice.\n4. Add yam paste in small lumps to thicken the soup.\n5. Add Utazi leaves and simmer for 2 mins.",
                'rating' => 4.8,
                'categories' => [2, 5],
                'ingredients' => [['id' => 2, 'quantity' => 1, 'unit' => 'piece']],
            ],
        ];

        foreach ($meals as $mealData) {
            $categories = $mealData['categories'];
            $ingredients = $mealData['ingredients'];
            unset($mealData['categories'], $mealData['ingredients']);

            $product = Product::updateOrCreate(
                ['name' => $mealData['name']],
                $mealData
            );

            $product->categories()->sync($categories);

            foreach ($ingredients as $ingredient) {
                $product->ingredients()->syncWithPivotValues([$ingredient['id']], [
                    'quantity' => $ingredient['quantity'],
                    'unit' => $ingredient['unit'],
                ], false);
            }
        }
    }
}
