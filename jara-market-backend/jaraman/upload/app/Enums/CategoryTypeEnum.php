<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum CategoryTypeEnum: string
{
    use InvokableCases, Names, Values;

    case FOOD = 1;
    case VENDOR = 2;
}
