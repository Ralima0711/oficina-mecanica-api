<?php

namespace App\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'nome' => array_merge($required, ['string', 'max:255']),
            'cpf' => array_merge($required, ['string', 'max:20']),
            'telefone' => array_merge($required, ['string', 'max:30']),
            'email' => array_merge($required, ['email', 'max:255']),
        ];
    }
}
