<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending    = 'pending';
    case Claimed    = 'claimed';
    case Processing = 'processing';
    case Delivered  = 'delivered';
    case Cancelled  = 'cancelled';
}
