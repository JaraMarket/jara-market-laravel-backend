<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Process;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Creates a full SQL backup of the database. Essential before bulk operations.')]
class DatabaseBackupTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $filename = 'backup_'.date('Y_m_d_His').'.sql';
        $path = storage_path('app/backups/'.$filename);

        if (! file_exists(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        // Use the absolute path to mysqldump from the System Path
        $mysqlDumpPath = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe';
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');

        $command = "\"{$mysqlDumpPath}\" --user={$dbUser} {$dbName} --result-file=\"{$path}\"";

        $result = Process::run($command);

        if ($result->successful()) {
            return Response::text("✅ Database backup created successfully: {$filename}\nLocation: storage/app/backups/");
        }

        return Response::text('❌ Backup failed: '.$result->errorOutput());
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return []; // No parameters needed
    }
}
