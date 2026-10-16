<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'merchant_code' => $this->merchant_code,
            'phone' => $this->phone,
            'status' => $this->wallet?->status?->value,
            'created_at' => $this->created_at,
        ];
    }
}
