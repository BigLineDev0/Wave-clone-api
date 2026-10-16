<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduledTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receiver_phone' => $this->receiver_phone,
            'amount' => $this->amount,
            'frequency' => $this->frequency->value,
            'next_run_at' => $this->next_run_at,
            'status' => $this->status->value,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
        ];
    }
}
