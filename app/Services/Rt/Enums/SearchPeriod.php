<?php

namespace App\Services\Rt\Enums;

enum SearchPeriod: int
{
    case All           = -1;
    case Today         = 1;
    case LastThreeDays = 3;
    case LastWeek      = 7;
    case LastTwoWeeks  = 14;
    case LastMonth     = 32;
}
