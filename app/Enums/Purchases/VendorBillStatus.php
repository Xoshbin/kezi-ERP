<?php

namespace App\Enums\Purchases;

enum VendorBillStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';
    case Paid = 'paid';
}
