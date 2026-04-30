<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AuditProductsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Jara Market Server')]
#[Version('0.0.1')]
#[Instructions('This server provides tools specifically for the JaraMarket marketplace management, such as product auditing.')]
class JaraMarketServer extends Server
{
    protected array $tools = [
        AuditProductsTool::class,
        PopulateProductsTool::class,
        DatabaseBackupTool::class,
        CreateProductTool::class,
        BulkImportTool::class,
        ImageAutoLinkTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
