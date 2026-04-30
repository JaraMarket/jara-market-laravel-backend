<?php

namespace App\Services\Payment;

use App\Contracts\UserRepositoryInterface;

class PaymentService
{
    public function __construct(public UserRepositoryInterface $userRepository) {}
}
