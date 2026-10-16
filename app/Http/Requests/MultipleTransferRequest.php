<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MultipleTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:100'
            ],

            'receivers' => [
                'required',
                'array',
                'min:2',
                'max:50',
            ],

            'receivers.*' => [
                'required',
                'string',
                'distinct',
            ],
        ];
    }
}
