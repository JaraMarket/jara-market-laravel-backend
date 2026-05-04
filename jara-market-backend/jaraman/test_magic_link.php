<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'test@example.com';
$id = 1;

$url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
    'api.verification.verify',
    now()->addMinutes(60),
    [
        'id' => $id,
        'hash' => sha1($email),
    ]
);

echo "Generated Magic Link:\n$url\n";
