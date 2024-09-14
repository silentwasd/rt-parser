<?php

namespace App\Services\Rt\Enums;

enum SearchOrder: int
{
    case Registered = 1;
    case TopicName  = 2;
    case Downloads  = 4;
    case Size       = 7;
    case Seeds      = 10;
    case Leeches    = 11;
}
