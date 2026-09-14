<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case MOBILE_MONEY = 'mobile_money';
    case BANK_TRANSFER = 'bank_transfer';
    case MIXED = 'mixed';
}
