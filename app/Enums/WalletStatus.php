<?php

namespace App\Enums;

enum WalletStatus: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
    case SUSPENDED = 'suspended';
}
