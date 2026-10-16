<?php
namespace App\Enums;

enum TransactionType: string
{
    case TRANSFER = 'transfer';

    case DEPOSIT = 'deposit';

    case WITHDRAW = 'withdraw';

    case MERCHANT = 'merchant';
    
    case FEE = 'fee';
}
