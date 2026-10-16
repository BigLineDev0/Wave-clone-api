<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Enums\WalletStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MerchantPaymentService
{
    /**
     * Effectuer un paiement vers un marchand.
     */
    public function pay(User $client, array $data): Transaction
    {
        /*
         * Recherche et validation du marchand.
         */
        $merchant = $this->findMerchant($data['merchant_code']);

        /*
         * Empêcher le paiement vers son propre compte.
         */
        if ($client->id === $merchant->id) {
            throw ValidationException::withMessages([
                'merchant_code' => [
                    'Vous ne pouvez pas effectuer un paiement vers votre propre compte.'
                ]
            ]);
        }

        /*
         * Vérifier que le wallet du client est actif.
         */
        if ($client->wallet->status !== WalletStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'wallet' => [
                    'Votre portefeuille est bloqué.'
                ]
            ]);
        }

        /*
         * Montant du paiement.
         */
        $amount = (float) $data['amount'];

        /*
         * Clé d'idempotence.
         */
        $idempotencyKey = $data['idempotency_key'];

        /*
         * Vérification rapide du solde.
         *
         * Cette vérification améliore les performances,
         * mais la vraie vérification sera faite sous verrou.
         */
        if ($client->wallet->balance < $amount) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Solde insuffisant.'
                ]
            ]);
        }

        return DB::transaction(function () use ($client,$merchant,$amount,$idempotencyKey) {

            /*
             * Vérifier si cette opération a déjà été exécutée.
             */
            $existingTransaction = Transaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingTransaction) {
                throw ValidationException::withMessages([
                    'idempotency_key' => [
                        'Vous avez deja paye avec ce meme montant.'
                ]
            ]);
            }

            /*
             * Verrouiller le wallet du client.
             */
            $clientWallet = $client->wallet()
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Verrouiller le wallet du marchand.
             */
            $merchantWallet = $merchant->wallet()
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Vérification définitive du solde
             * après acquisition du verrou.
             */
            if ($clientWallet->balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => [
                        'Solde insuffisant.'
                    ]
                ]);
            }

            /*
             * Débit du client.
             */
            $clientWallet->decrement('balance',$amount);

            /*
             * Crédit du marchand.
             */
            $merchantWallet->increment('balance',$amount);

            /*
             * Création de la transaction.
             */
            return Transaction::create([
                'idempotency_key' => $idempotencyKey,
                'sender_wallet_id' => $clientWallet->id,
                'receiver_wallet_id' => $merchantWallet->id,
                'amount' => $amount,
                'fee' => 0,
                'status' => TransactionStatus::COMPLETED,
                'type' => TransactionType::MERCHANT,
            ])->load([
                'senderWallet.user',
                'receiverWallet.user',
            ]);
        });
    }

    /**
     * Récupérer un marchand actif.
     */
    public function findMerchant(string $merchantCode): User
    {
        $merchant = User::query()
            ->where('merchant_code', $merchantCode)
            ->where('role', UserRole::MERCHANT)
            ->with('wallet')
            ->first();

        if (!$merchant) {
            throw ValidationException::withMessages([
                'merchant_code' => [
                    'Marchand introuvable.'
                ]
            ]);
        }

        if (
            !$merchant->wallet ||
            $merchant->wallet->status !== WalletStatus::ACTIVE
        ) {
            throw ValidationException::withMessages([
                'merchant_code' => [
                    'Le portefeuille du marchand est bloqué.'
                ]
            ]);
        }

        return $merchant;
    }
}
