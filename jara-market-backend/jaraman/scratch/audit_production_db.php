<?php
// Production Audit Script (Read-Only)
$host = 'switchyard.proxy.rlwy.net';
$port = '47446';
$db   = 'railway';
$user = 'root';
$pass = 'KQtOLICkxlZTTeKnriqSogFsJHtriXxu';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     echo "✅ Connected to Production Railway Database!\n\n";

     echo "--- TABLES ---\n";
     $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
     print_r($tables);

     echo "\n--- TOTAL PRODUCT COUNT ---\n";
     $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
     echo "Total Products in Production: $totalProducts\n";

     echo "\n--- EXPORTING NAMES ---\n";
     $allNames = $pdo->query("SELECT name FROM products")->fetchAll(PDO::FETCH_COLUMN);
     file_put_contents('scratch/all_production_names.txt', implode("\n", $allNames));
     echo "Exported " . count($allNames) . " names to scratch/all_production_names.txt\n";

     if (in_array('products', $tables)) {
         echo "\n--- PRODUCT COLUMNS ---\n";
         $columns = $pdo->query("DESCRIBE products")->fetchAll();
         foreach ($columns as $col) {
             echo "{$col['Field']} ({$col['Type']})\n";
         }

         echo "\n--- SAMPLE IMAGE PATHS (First 3) ---\n";
         $images = $pdo->query("SELECT image_url FROM products WHERE image_url IS NOT NULL LIMIT 3")->fetchAll();
         print_r($images);
     }

} catch (\PDOException $e) {
     echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
