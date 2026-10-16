<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Enums\WalletStatus;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DistributorService
{

    public function __construct(
        protected FeeService $feeService
    ) {}
    /**
     * Effectuer un dépôt (cash-in).
     */
    public function cashIn(User $distributor, array $data): Transaction
    {
        // 1. Vérifier que l'utilisateur est un distributeur.
        $this->ensureDistributor($distributor);

        // 2. Récupérer le client.
        $client = $this->getClient($data['phone']);

        // 3. Empêcher une opération vers son propre compte.
        $this->ensureDifferentUsers($distributor, $client);

        // 4. Vérifier que les deux wallets sont actifs.
        $this->ensureWalletsAreActive($distributor, $client);

        $amount = (float) $data['amount'];

        $fee = $this->feeService->calculateCashInFee($amount);

        $totalAmount = $amount + $fee;

        // 5. Récupérer la clé d'idempotence.
        $idempotencyKey = $data['idempotency_key'];

        $this->ensureSufficientBalance($distributor->wallet, $totalAmount);

        $system = $this->getSystemUser();

        /*
         * Transaction DB.
         */
        return DB::transaction(function () use (
            $distributor,
            $client,
            $system,
            $amount,
            $fee,
            $totalAmount,
            $idempotencyKey
        ) {

            /*
             * Vérifier si cette opération existe déjà.
             */
            $existingTransaction = Transaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingTransaction) {
                return $existingTransaction->load([
                    'senderWallet.user',
                    'receiverWallet.user',
                ]);
            }

            /*
             * Verrouiller le wallet du distributeur.
             */
            $distributorWallet = $distributor->wallet()
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Verrouiller le wallet du client.
             */
            $clientWallet = $client->wallet()
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Verrouiller le wallet du system.
             */
            $systemWallet = $system->wallet()
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Vérification définitive du solde
             * après acquisition du verrou.
             */
            $this->ensureSufficientBalance($distributorWallet, $totalAmount);

            /*
             * Débiter le distributeur.
             */
            $distributorWallet->decrement('balance', $totalAmount);

            /*
             * Créditer le client.
             */
            $clientWallet->increment('balance', $amount);

            $systemWallet->increment('balance', $fee);

            /*
            * Enregistrer la transaction principale.
            */
            $transaction = Transaction::create([
                'idempotency_key' => $idempotencyKey,
                'sender_wallet_id' => $distributorWallet->id,
                'receiver_wallet_id' => $clientWallet->id,
                'amount' => $amount,
                'fee' => $fee,
                'status' => TransactionStatus::COMPLETED,
                'type' => TransactionType::DEPOSIT,
            ]);

            /*
            * Enregistrer la transaction de commission.
            */
            Transaction::create([
                'idempotency_key' => $idempotencyKey . '-fee',
                'sender_wallet_id' => $distributorWallet->id,
                'receiver_wallet_id' => $systemWallet->id,
                'amount' => $fee,
                'fee' => 0,
                'status' => TransactionStatus::COMPLETED,
                'type' => TransactionType::FEE,
            ]);

            /*
            * Retourner uniquement la transaction principale.
            */
            return $transaction->load([
                'senderWallet.user',
                'receiverWallet.user',
            ]);

        });
    }


    /**
     * Effectuer un retrait (cash-out).
     */
    public function cashOut(User $distributor, array $data): Transaction
    {
        // 1. Vérifier que l'utilisateur est un distributeur.
        $this->ensureDistributor($distributor);

        // 2. Récupérer le client.
        $client = $this->getClient($data['phone']);

        // 3. Empêcher une opération vers son propre compte.
        $this->ensureDifferentUsers($distributor, $client);

        // 4. Vérifier que les deux wallets sont actifs.
        $this->ensureWalletsAreActive($distributor, $client);

        $amount = (float) $data['amount'];

        // Calculer les frais.
        $fee = $this->feeService->calculateCashOutFee($amount);

        // Montant total débité du client.
        $totalAmount = $amount + $fee;

        // 5. Récupérer la clé d'idempotence.
        $idempotencyKey = $data['idempotency_key'];

        /*
         * Pour un cash-out, c'est le client
         * qui doit avoir suffisamment de fonds.
         */
        $this->ensureSufficientBalance($client->wallet, $totalAmount);

        // Récupérer le compte système.
        $system = $this->getSystemUser();

        /*
         * Transaction DB.
         */
        return DB::transaction(function () use (
            $distributor,
            $client,
            $system,
            $amount,
            $fee,
            $totalAmount,
            $idempotencyKey) {

            /*
             * Vérifier si cette opération existe déjà.
             */
            $existingTransaction = Transaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingTransaction) {
                return $existingTransaction->load([
                    'senderWallet.user',
                    'receiverWallet.user',
                ]);
            }

            /*
             * Verrouiller le wallet du client.
             */
            $clientWallet = $client->wallet()
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Verrouiller le wallet du distributeur.
             */
            $distributorWallet = $distributor->wallet()
                ->lockForUpdate()
                ->firstOrFail();

            /*
            * Verrouiller le wallet système.
            */
            $systemWallet = $system->wallet()
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Vérification définitive du solde
             * après acquisition du verrou.
             */
            $this->ensureSufficientBalance($clientWallet, $totalAmount);

            /*
             * Débiter le client.
             */
            $clientWallet->decrement('balance',$totalAmount);

            /*
             * Créditer le distributeur.
             */
            $distributorWallet->increment('balance', $amount);

            /*
             * Enregistrer la transaction.
             */
            return Transaction::create([
                'idempotency_key' => $idempotencyKey,
                'sender_wallet_id' => $clientWallet->id,
                'receiver_wallet_id' => $distributorWallet->id,
                'amount' => $amount,
                'fee' => $fee,
                'status' => TransactionStatus::COMPLETED,
                'type' => TransactionType::WITHDRAW,
            ]);

            /*
            * Enregistrer la transaction de commission.
            */
            Transaction::create([
                'idempotency_key' => $idempotencyKey . '-fee',
                'sender_wallet_id' => $clientWallet->id,
                'receiver_wallet_id' => $systemWallet->id,
                'amount' => $fee,
                'fee' => 0,
                'status' => TransactionStatus::COMPLETED,
                'type' => TransactionType::FEE,
            ]);

            /*
            * Retourner uniquement la transaction principale.
            */
            return $transaction->load([
                'senderWallet.user',
                'receiverWallet.user',
            ]);
        });
    }


    /**
     * Récupérer un client par son numéro de téléphone.
     */
    private function getClient(string $phone): User
    {
        $client = User::query()
            ->where('phone', $phone)
            ->where('role', UserRole::CLIENT)
            ->with('wallet')
            ->first();

        if (!$client) {
            throw ValidationException::withMessages([
                'phone' => [
                    'Client introuvable.'
                ]
            ]);
        }

        return $client;
    }


    /**
     * Vérifier que l'utilisateur connecté est un distributeur.
     */
    private function ensureDistributor(User $distributor): void
    {
        if ($distributor->role !== UserRole::DISTRIBUTOR) {
            throw ValidationException::withMessages([
                'user' => [
                    'Seuls les distributeurs peuvent effectuer cette opération.'
                ]
            ]);
        }
    }


    /**
     * Empêcher une opération entre le même utilisateur.
     */
    private function ensureDifferentUsers(User $distributor, User $client): void {
        if ($distributor->id === $client->id) {
            throw ValidationException::withMessages([
                'phone' => [
                    'Vous ne pouvez pas effectuer cette opération sur votre propre compte.'
                ]
            ]);
        }
    }


    /**
     * Vérifier que les wallets sont actifs.
     */
    private function ensureWalletsAreActive(User $distributor, User $client): void {
        if ($distributor->wallet->status !== WalletStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'wallet' => [
                    'Votre portefeuille est bloqué.'
                ]
            ]);
        }

        if ($client->wallet->status !== WalletStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'phone' => [
                    'Le portefeuille du client est bloqué.'
                ]
            ]);
        }
    }


    /**
     * Vérifier que le wallet possède suffisamment de fonds.
     */
    private function ensureSufficientBalance(Wallet $wallet, float $amount): void {
        if ($wallet->balance < $amount) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Solde insuffisant.'
                ]
            ]);
        }
    }

    /**
     * Récupérer le compte système.
     */
    private function getSystemUser(): User
    {
        $system = User::query()
            ->where('role', UserRole::SYSTEM)
            ->with('wallet')
            ->first();

        if (!$system) {
            throw ValidationException::withMessages([
                'system' => [
                    'Le compte système est introuvable.'
                ]
            ]);
        }

        return $system;
    }
}
