<?php

use App\Models\User;
use App\Notifications\OtpNotification;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = clone User::first();
if (!$user) {
    echo "No users found in database to send OTP to.\n";
    exit(1);
}

// Override email for testing to ensure it goes to you
$user->email = 'iudofa0@gmail.com'; 

$otp = rand(100000, 999999);
echo "Dispatching test OTP: $otp to $user->email...\n";

try {
    $user->notify(new OtpNotification($otp));
    echo "✅ Job successfully dispatched to the queue!\n";
} catch (\Exception $e) {
    echo "❌ Error dispatching job: " . $e->getMessage() . "\n";
}
