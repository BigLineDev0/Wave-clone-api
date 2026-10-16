<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class Transaction extends Model
{
    use HasFactory;
    // Les attributs qui sont assignables en masse
    protected $fillable = [
        'reference',
        'idempotency_key',
        'sender_wallet_id',
        'receiver_wallet_id',
        'amount',
        'fee',
        'status',
        'type',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'status' => TransactionStatus::class,
            'type' => TransactionType::class,
        ];
    }

    // un transaction envoyée appartient à un portefeuille
    public function senderWallet()
    {
        return $this->belongsTo(Wallet::class, 'sender_wallet_id');
    }

    // un transaction reçue appartient à un portefeuille
    public function receiverWallet()
    {
        return $this->belongsTo(Wallet::class, 'receiver_wallet_id');
    }

    // Génère une référence unique pour chaque transaction avant sa création
    protected static function booted()
    {
        static::creating(function ($transaction) {
            $transaction->reference = Str::uuid();
        });
    }

    /**
     * Scope : transactions d'un utilisateur.
     */
    public function scopeForUser(Builder $query, User $user): Builder {

        return $query->where(function ($query) use ($user) {
            $query->whereHas('senderWallet', function ($q) use ($user) {
                $q->where('user_id', $user->id);

            })->orWhereHas('receiverWallet', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        });
    }

    /**
     * Scope : effectue untransfert
     */
    public function scopeSent(Builder $query,User $user): Builder {
        return $query->whereHas(
            'senderWallet', fn ($q) => $q->where('user_id', $user->id)
        );

    }

    /**
     * Scope : recevoir un transfert
     */

    public function scopeReceived(Builder $query,User $user): Builder {
        return $query->whereHas(
            'receiverWallet', fn ($q) => $q->where('user_id', $user->id)
        );

    }

    /**
     * Scope : transfert complete
    */

    public function scopeCompleted(Builder $query): Builder {
        return $query->where('status', TransactionStatus::COMPLETED);
    }

    /**
     * Scope : transaction transfert
    */
    public function scopeTransfert(Builder $query): Builder {
        return $query->where('type', TransactionType::TRANSFERT);
    }
    /**
     * scope  : transaction annule
     */
    public function scopeCanceled(Builder $query): Builder {
        return $query->where('status', TransactionStatus::CANCELLED);
    }

}
