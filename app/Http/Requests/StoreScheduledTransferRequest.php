<?php

namespace App\Http\Requests;

use App\Enums\ScheduledTransferFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreScheduledTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_phone' => [
                'required',
                'string',
                'exists:users,phone',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:100',
            ],

            'frequency' => [
                'required',
                new Enum(ScheduledTransferFrequency::class),
            ],

            'next_run_at' => [
                'required',
                'date',
                'after:now',
            ],
        ];
    }
}
