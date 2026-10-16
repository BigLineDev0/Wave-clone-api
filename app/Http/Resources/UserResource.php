<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transforme le modèle en tableau.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'role' => $this->role?->value,
            'wallet' => [
                'balance' => $this->wallet->balance,
                'plafond' => $this->wallet->plafond,
                'status' => $this->wallet->status,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
