<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transforme le modèle en tableau.
     */
    public function toArray(Request $request): array
    {
        return [

            'reference' => $this->reference,
            'amount' => (float) $this->amount,
            'fee' => (float) $this->fee,
            'direction' => $this->senderWallet->user_id === $request->user()->id
            ? 'sent'
            : 'received',
            'total' => (float) ($this->amount + $this->fee),
            'status' => $this->status->value,
            'type' => $this->type->value,
            'sender' => [
                'id' => $this->senderWallet->user->id,
                'name' => $this->senderWallet->user->full_name,
                'phone' => $this->senderWallet->user->phone,
            ],

            'receiver' => [
                'id' => $this->receiverWallet->user->id,
                'name' => $this->receiverWallet->user->full_name,
                'phone' => $this->receiverWallet->user->phone,
            ],

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),

        ];
    }
}
