<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_code' => [
                'required',
                'string',
                'exists:users,merchant_code',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:5',
            ],
            'idempotency_key' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'merchant_code.required' => 'Le code commerçant est requis.',
            'merchant_code.string' => 'Le code commerçant doit être une chaîne de caractères.',
            'merchant_code.exists' => 'Le code commerçant est invalide.',
            'amount.required' => 'Le montant est requis.',
            'amount.numeric' => 'Le montant doit être un nombre.',
            'amount.min' => 'Le montant doit être au minimum 5.',
        ];
    }
}
