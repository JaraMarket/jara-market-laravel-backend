<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum CurrencyEnum: string
{
    use InvokableCases, Names, Values;

    case NGN = 'NGN';
    case USD = 'USD';
}
