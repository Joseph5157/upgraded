<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Delivered  = 'delivered';
    case Cancelled  = 'cancelled';
}
