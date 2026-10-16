<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\WalletStatus;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'plafond',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'plafond' => 'decimal:2',
        'status' => WalletStatus::class,
    ];

    // Vérifie si le portefeuille peut effectuer un retrait d'un certain montant
    public function canWithdraw(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    // Vérifie si le portefeuille a atteint la limite de plafond
    public function hasReachedLimit(): bool
    {
        return $this->balance >= $this->plafond;
    }

    // déposer de l'argent dans le portefeuille
    public function deposit(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    // retirer de l'argent du portefeuille
    public function withdraw(float $amount): void
    {
        $this->decrement('balance', $amount);
    }

    // un portefeuille appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // un portefeuille a plusieurs transactions envoyées
    public function sentTransactions()
    {
        return $this->hasMany(Transaction::class, 'sender_wallet_id');
    }

    // un portefeuille a plusieurs transactions reçues
    public function receivedTransactions()
    {
        return $this->hasMany(Transaction::class, 'receiver_wallet_id');
    }
}
