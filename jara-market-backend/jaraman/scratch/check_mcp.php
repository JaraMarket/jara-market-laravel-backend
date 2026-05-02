<?php

use App\Mcp\Tools\AuditProductsTool;
use Laravel\Mcp\Request;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- Verifying JaraMarket MCP Tool: AuditProductsTool ---\n";

try {
    $tool = new AuditProductsTool();
    $request = new Request(['limit' => 5]);
    $response = $tool->handle($request);
    
    echo "MCP Tool Response:\n";
    echo "------------------\n";
    echo (string) $response->content();
    echo "\n------------------\n";
    echo "✅ MCP Tool is functional and communicating with the database.\n";
} catch (\Exception $e) {
    echo "❌ Error verifying MCP Tool: " . $e->getMessage() . "\n";
}
