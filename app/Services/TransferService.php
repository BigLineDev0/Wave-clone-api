<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Enums\WalletStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferService
{
    public function __construct(
        protected FeeService $feeService
    ) {
    }

    /**
     * Effectuer un transfert.
     */
    public function transfer(User $sender, array $data): Transaction
    {
        $receiver = $this->getReceiver($data['receiver_phone']);

        $this->ensureNotSelfTransfer($sender, $receiver);

        $this->ensureWalletsAreActive($sender, $receiver);

        $amount = (float) $data['amount'];

        $fee = $this->feeService->calculateTransferFee($amount);

        $totalAmount = $amount + $fee;

        return DB::transaction(function () use ($sender, $receiver, $amount, $fee, $totalAmount) {

            // Verrouiller les deux wallets
            $senderWallet = Wallet::whereKey($sender->wallet->id)
                ->lockForUpdate()
                ->first();

            $receiverWallet = Wallet::whereKey($receiver->wallet->id)
                ->lockForUpdate()
                ->first();

            // Revérifier le solde après verrouillage
            $this->ensureSufficientBalance($senderWallet,$totalAmount);

            // Débiter l'expéditeur
            $senderWallet->decrement('balance',$totalAmount);

            // Créditer le destinataire
            $receiverWallet->increment('balance',$amount);

            // Enregistrer la transaction
            $transaction = Transaction::create([
                'sender_wallet_id'   => $senderWallet->id,
                'receiver_wallet_id' => $receiverWallet->id,
                'amount'             => $amount,
                'fee'                => $fee,
                'status'             => TransactionStatus::COMPLETED,
                'type'               => TransactionType::TRANSFER,
            ]);

            return $transaction->load([
                'senderWallet.user',
                'receiverWallet.user',
            ]);
        });
    }

    /**
     * Effectuer un transfert multiple
     */
    public function transferMultiple(User $sender, array $data) {
        return DB::transaction(function () use ($sender, $data) {

            /*
            * Verrouiller le portefeuille de l'expéditeur.
            *
            * Tant que cette transaction SQL n'est pas terminée,
            * une autre opération ne pourra pas modifier ce wallet
            * simultanément.
            */
            $senderWallet = Wallet::query()
                ->whereKey($sender->wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (float) $data['amount'];
            $receivers = $data['receivers'];

            /*
            * Récupération des destinataires
            */
            $users = User::whereIn('phone', $receivers)
                ->with('wallet')
                ->get();

            /*
            * Vérifier que tous les numéros existent.
            */
            if ($users->count() !== count($receivers)) {

                throw ValidationException::withMessages([
                    'receivers' => [
                        'Un ou plusieurs destinataires sont introuvables.'
                    ]
                ]);
            }


            /*
            * Vérifier que l'expéditeur ne figure pas
            * parmi les destinataires.
            */
            foreach ($users as $receiver) {
                $this->ensureNotSelfTransfer($sender, $receiver);
                $this->ensureWalletsAreActive($sender, $receiver);
            }

            /*
            * Calcul des frais pour UN transfert.
            */
            $fee = $this->feeService->calculateTransferFee($amount);

            $totalPerTransfer = $amount + $fee;

            /*
            * Montant total nécessaire.
            */
            $totalAmount = $totalPerTransfer * $users->count();

            /*
            * Vérifier le solde avant de commencer.
            */
            $this->ensureSufficientBalance(
                $senderWallet,
                $totalAmount
            );

            $transactions = [];

            /*
            * Effectuer chaque transfert.
            */
            foreach ($users as $receiver) {

                $senderWallet->decrement(
                    'balance',
                    $totalPerTransfer
                );

                $receiver->wallet->increment(
                    'balance',
                    $amount
                );

                $transaction = Transaction::create([
                    'sender_wallet_id' => $senderWallet->id,
                    'receiver_wallet_id' => $receiver->wallet->id,
                    'amount' => $amount,
                    'fee' => $fee,
                    'status' => TransactionStatus::COMPLETED,
                    'type' => TransactionType::TRANSFER,
                ]);

                $transactions[] = $transaction->load([
                    'senderWallet.user',
                    'receiverWallet.user',
                ]);
            }

            return $transactions;
        });
    }

    /**
     * Récupérer le destinataire.
     */
    private function getReceiver(string $phone): User
    {
        $receiver = User::where('phone', $phone)->with('wallet')->first();

        if (!$receiver) {
            throw ValidationException::withMessages([
                'receiver_phone' => [
                    'Le destinataire est introuvable.',
                ],
            ]);
        }

        return $receiver;
    }

    /**
     * Empêcher le transfert vers soi-même.
     */
    private function ensureNotSelfTransfer(User $sender, User $receiver): void
    {
        if ($sender->id === $receiver->id) {
            throw ValidationException::withMessages([
                'receiver_phone' => [
                    'Vous ne pouvez pas vous transférer de l\'argent à vous-même.',
                ],
            ]);
        }
    }

    /**
     * Vérifier que les wallets sont actifs.
     */
    private function ensureWalletsAreActive(User $sender, User $receiver): void
    {
        if ($sender->wallet->status !== WalletStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'wallet' => [
                    'Votre portefeuille est bloqué.',
                ],
            ]);
        }

        if ($receiver->wallet->status !== WalletStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'receiver_phone' => [
                    'Le portefeuille du destinataire est bloqué.',
                ],
            ]);
        }
    }

    /**
     * Vérifier le solde.
     */
    private function ensureSufficientBalance( Wallet $wallet, float $totalAmount): void {

        if ($wallet->balance < $totalAmount) {
            throw ValidationException::withMessages([
                'amount' => [
                    'Solde insuffisant.',
                ],
            ]);
        }
    }

    /**
     * Annuler un transfert
     */
    public function cancel(User $user,Transaction $transaction): Transaction {

        return DB::transaction(function () use ($user, $transaction) {

            /*
            * On recharge la transaction depuis la base
            * et on la verrouille.
            */
            $transaction = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            * Charger les wallets.
            */
            $senderWallet = Wallet::query()
                ->whereKey($transaction->sender_wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            $receiverWallet = Wallet::query()
                ->whereKey($transaction->receiver_wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            * 1. Vérifier que l'utilisateur est bien
            * l'expéditeur.
            */
            $this->ensureCanCancel($user, $transaction);

            /*
            * 2. Vérifier le statut.
            */
            $this->ensureTransactionCanBeCancelled($transaction);

            /*
            * 3. Vérifier le délai de 30 minutes.
            */
            $this->ensureWithinCancellationPeriod($transaction);

            /*
            * 4. Montant du transfert.
            */
            $amount = (float) $transaction->amount;

            /*
            * 5. Montant total à rembourser.
            *
            * On rembourse :
            *
            * montant + frais
            */
            $refundAmount = $this->feeService->calculateRefundAmount($transaction);

            /*
            * 6. Vérifier que le destinataire possède
            * encore le montant transféré.
            */
            $this->ensureReceiverHasEnoughBalance($receiverWallet, $amount);

            /*
            * 7. Rembourser l'expéditeur.
            */
            $senderWallet->increment('balance', $refundAmount);

            /*
            * 8. Débiter le destinataire.
            *
            * On retire uniquement le montant reçu.
            * Les frais sont remboursés séparément à l'expéditeur.
            */
            $receiverWallet->decrement('balance', $amount);

            /*
            * 9. Marquer la transaction comme annulée.
            */
            $transaction->update([
                'status' => TransactionStatus::CANCELLED,
                'cancelled_at' => now(),
            ]);

            /*
            * 10. Retourner la transaction avec
            * les relations nécessaires au Resource.
            */
            return $transaction->load([
                'senderWallet.user',
                'receiverWallet.user',
            ]);
        });
    }

    /**
    * Vérifie que l'utilisateur est bien l'expéditeur.
    */
    private function ensureCanCancel(User $user,Transaction $transaction): void {
        if ($transaction->senderWallet->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'transaction' => [
                    'Vous n\'êtes pas autorisé à annuler cette transaction.'
                ]
            ]);
        }
    }

    /**
     * Vérifie que la transaction est encore annulable.
     */
    private function ensureTransactionCanBeCancelled(Transaction $transaction): void {
        if ($transaction->status !== TransactionStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'transaction' => [
                    'Cette transaction ne peut pas être annulée.'
                ]
            ]);
        }
    }

    /**
     * Vérifie que la demande d'annulation intervient
     * dans les 30 minutes suivant le transfert.
     */
    private function ensureWithinCancellationPeriod(Transaction $transaction): void {
        if ($transaction->created_at->addMinutes(30)->isPast()) {
            throw ValidationException::withMessages([
                'transaction' => [
                    'Cette transaction ne peut plus être annulée après 30 minutes.'
                ]
            ]);
        }
    }

    /**
    * Vérifie que le destinataire possède encore
    * le montant nécessaire pour permettre le remboursement.
    */
    private function ensureReceiverHasEnoughBalance(Wallet $wallet, float $amount): void {
        if ($wallet->balance < $amount) {
            throw ValidationException::withMessages([
                'transaction' => [
                    'Le solde du destinataire est insuffisant pour annuler cette transaction.'
                ]
            ]);
        }
    }
}
