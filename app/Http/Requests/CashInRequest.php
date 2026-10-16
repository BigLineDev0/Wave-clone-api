<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CashInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'exists:users,phone',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:500',
            ],

            'idempotency_key' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }
}
