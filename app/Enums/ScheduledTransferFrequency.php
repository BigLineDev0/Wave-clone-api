<?php
namespace App\Enums;

enum ScheduledTransferFrequency: string
{
    case DAILY = 'daily';        // Jour
    case WEEKLY = 'weekly';     // semaine
    case MONTHLY = 'monthly';  // Mois
}
