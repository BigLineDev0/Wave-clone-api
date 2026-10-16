<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;

class TransactionService
{
    public function history(User $user)
    {
        return Transaction::query()->with(['senderWallet.user', 'receiverWallet.user'])
            ->forUser($user)
            ->latest()
            ->paginate(10);

    }
}
