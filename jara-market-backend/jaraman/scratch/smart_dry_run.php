<?php
// Smart Dry Run (Read-Only)
// Matches local files to Production Product Names
require 'vendor/autoload.php';

$host = 'switchyard.proxy.rlwy.net';
$port = '47446';
$db   = 'railway';
$user = 'root';
$pass = 'KQtOLICkxlZTTeKnriqSogFsJHtriXxu';
$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    // 1. Get next 20 local images (Offset by 45)
    $localFiles = glob('storage/app/public/products/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    $samples = array_slice($localFiles, 45, 20);
    
    echo "--- DRY RUN #5: VERIFICATION MAPPING (Batch 46-65) ---\n";
    printf("%-30s | %-30s | %-20s\n", "Local File", "Production Name", "Match Reason");
    echo str_repeat("-", 85) . "\n";

    // Load master list with timestamps
    $stmt = $pdo->query("SELECT name, created_at FROM products");
    $masterProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($samples as $path) {
        $filename = basename($path);
        
        $fileTimestamp = null;
        if (preg_match('/^([0-9]{10})_/', $filename, $matches)) {
            $fileTimestamp = (int)$matches[1];
        }
        
        $cleanName = preg_replace('/^[0-9]+_/', '', pathinfo($filename, PATHINFO_FILENAME));
        $cleanName = strtolower(str_replace(['_', '-'], ' ', $cleanName));
        
        $bestMatch = null;
        $matchReason = "";
        
        // PASS 1: SEARCH NAMES
        foreach ($masterProducts as $prod) {
            $lowerProd = strtolower($prod['name']);
            if (strpos($lowerProd, $cleanName) !== false || strpos($cleanName, $lowerProd) !== false) {
                $bestMatch = $prod['name'];
                $matchReason = "✅ Name Match";
                break;
            }
        }
        
        // PASS 2: TIMEZONE-CORRECTED TIME PROXIMITY
        if (!$bestMatch && $fileTimestamp) {
            $closestProd = null;
            $minDiff = PHP_INT_MAX;
            
            foreach ($masterProducts as $prod) {
                $prodTimestamp = strtotime($prod['created_at']);
                
                // Test exact, +5 hours, and -5 hours
                $diffRaw = abs($fileTimestamp - $prodTimestamp);
                $diffPlus = abs($fileTimestamp - ($prodTimestamp + 18000));
                $diffMinus = abs($fileTimestamp - ($prodTimestamp - 18000));
                
                $bestDiff = min($diffRaw, $diffPlus, $diffMinus);
                
                if ($bestDiff < $minDiff) {
                    $minDiff = $bestDiff;
                    $closestProd = $prod['name'];
                }
            }
            
            // Accept if created within a 1-hour window (3600 seconds) after correction
            if ($minDiff < 3600) { 
                $bestMatch = $closestProd;
                $matchReason = "⏱️ Timezone Match (" . $minDiff . "s gap)";
            } else {
                $bestMatch = $closestProd;
                $matchReason = "⚠️ Time Match (Wide gap: " . $minDiff . "s)";
            }
        }
        
        $displayName = $bestMatch ? $bestMatch : "❌ NO MATCH";
        printf("%-30s | %-30s | %-20s\n", substr($filename, 0, 30), substr($displayName, 0, 30), $matchReason);
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
