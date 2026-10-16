<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    /**
     * Autoriser la requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation.
     */
    public function rules(): array
    {
        return [
            'receiver_phone' => [
                'required',
                'string',
                'exists:users,phone'
            ],

            'amount' => [
                'required',
                'numeric',
                'min:5'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_phone.required' => 'Le numéro du destinataire est obligatoire.',
            'receiver_phone.exists'   => 'Ce numéro est introuvable.',

            'amount.required' => 'Le montant est obligatoire.',
            'amount.numeric'  => 'Le montant doit être numérique.',
            'amount.min'      => 'Le montant minimum est de 5 FCFA.',
        ];
    }
}
