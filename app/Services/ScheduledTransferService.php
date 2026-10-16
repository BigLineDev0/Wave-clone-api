<?php

namespace App\Services;

use App\Enums\ScheduledTransferStatus;
use App\Enums\ScheduledTransferFrequency;
use App\Models\ScheduledTransfer;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduledTransferService
{
    public function __construct(
        protected TransferService $transferService
    ) {}

    public function create(User $user, array $data): ScheduledTransfer {

        /*
         * Vérifier que le destinataire existe.
         */
        $receiver = User::where('phone', $data['receiver_phone'])->first();

        if (!$receiver) {
            throw ValidationException::withMessages([
                'receiver_phone' => ['Le destinataire est introuvable.']
            ]);
        }

        /*
         * Empêcher l'utilisateur de se programmer
         * un transfert vers son propre numéro.
         */
        if ($receiver->id === $user->id) {
            throw ValidationException::withMessages([
                'receiver_phone' => [
                    'Vous ne pouvez pas programmer un transfert vers vous-même.'
                ]
            ]);
        }

        /*
         * Création de la planification.
         */
        return $user->scheduledTransfers()->create([
            'receiver_phone' => $data['receiver_phone'],
            'amount' => $data['amount'],
            'frequency' => $data['frequency'],
            'next_run_at' => $data['next_run_at'],
            'status' => ScheduledTransferStatus::ACTIVE,
        ]);
    }

    /**
     * Annuler Planification Transfert
    */

    public function cancel(User $user, ScheduledTransfer $scheduledTransfer): ScheduledTransfer {

        // Vérifier que la planification appartient à l'utilisateur
        if ($scheduledTransfer->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'scheduled_transfer' => [
                    'Vous n\'êtes pas autorisé à annuler cette planification.'
                ]
            ]);
        }

        // Vérifier qu'elle est encore active
        if ($scheduledTransfer->status !== ScheduledTransferStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'scheduled_transfer' => [
                    'Cette planification est déjà annulée.'
                ]
            ]);
        }

        // Annulation logique
        $scheduledTransfer->update([
            'status' => ScheduledTransferStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);

        return $scheduledTransfer->refresh();
    }

    
    /**
     * Execute automatique le transfert planifie
     */
    public function executeDueTransfers(): void
    {
        $scheduledTransfers = ScheduledTransfer::query()
            ->where('status', ScheduledTransferStatus::ACTIVE)
            ->where('next_run_at', '<=', now())
            ->with('user')
            ->get();

        foreach ($scheduledTransfers as $scheduledTransfer) {

            DB::transaction(function () use ($scheduledTransfer) {

                $user = $scheduledTransfer->user;

                /*
                * Réutilise notre logique de transfert existante.
                */
                $this->transferService->transfer(
                    $user,
                    [
                        'receiver_phone' => $scheduledTransfer->receiver_phone,
                        'amount' => $scheduledTransfer->amount,
                    ]
                );

                /*
                * Calculer la prochaine exécution.
                */
                $this->updateNextRunAt($scheduledTransfer);
            });
        }
    }


    // Fonction pour calculer la prochaine planification
    private function updateNextRunAt(ScheduledTransfer $scheduledTransfer): void {

        $nextRun = $scheduledTransfer->next_run_at->copy();

        match ($scheduledTransfer->frequency) {

            ScheduledTransferFrequency::DAILY => $nextRun->addDay(),

            ScheduledTransferFrequency::WEEKLY => $nextRun->addWeek(),

            ScheduledTransferFrequency::MONTHLY => $nextRun->addMonth(),
        };

        $scheduledTransfer->update(['next_run_at' => $nextRun]);
    }
}
