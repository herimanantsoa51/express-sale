<?php

namespace App\Enums;

enum SaleType: string
{
    case IMMEDIATE = 'immediate';
    case CREDIT = 'credit';
    case RESERVATION = 'reservation';
}
