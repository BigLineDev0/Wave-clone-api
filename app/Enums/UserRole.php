<?php
namespace App\Enums;

enum UserRole: string
{
    case CLIENT = 'client';
    case DISTRIBUTOR = 'distributeur';
    case MERCHANT = 'marchand';
    case SYSTEM = 'system';
}
