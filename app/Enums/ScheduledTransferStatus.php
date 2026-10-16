<?php
namespace App\Enums;

enum ScheduledTransferStatus: string
{
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
}
