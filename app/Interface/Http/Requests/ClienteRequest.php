<?php

namespace App\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CAMADA DE INTERFACE — Form Request
 * Valida e autoriza as requisições HTTP para Cliente.
 */
class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome'      => ['required', 'string', 'max:255'],
            'cpf'       => ['required', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/'],
            'telefone'  => ['required', 'string', 'max:20'],
            'email'     => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'      => 'O nome é obrigatório.',
            'nome.max'           => 'O nome não pode ter mais de 255 caracteres.',
            'cpf.required'       => 'O CPF é obrigatório.',
            'cpf.regex'          => 'O CPF deve estar no formato: XXX.XXX.XXX-XX',
            'telefone.required'  => 'O telefone é obrigatório.',
            'telefone.max'       => 'O telefone não pode ter mais de 20 caracteres.',
            'email.required'     => 'O email é obrigatório.',
            'email.email'        => 'O email deve ser válido.',
        ];
    }
}
