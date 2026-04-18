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

    protected function prepareForValidation(): void
    {
        if ($this->has('cpf')) {
            $this->merge([
                'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'nome'      => ['required', 'string', 'max:255'],
            'cpf'       => ['required', 'string', 'regex:/^\d{11}$/'],
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
            'cpf.regex'          => 'O CPF deve conter 11 dígitos válidos.',
            'telefone.required'  => 'O telefone é obrigatório.',
            'telefone.max'       => 'O telefone não pode ter mais de 20 caracteres.',
            'email.required'     => 'O email é obrigatório.',
            'email.email'        => 'O email deve ser válido.',
        ];
    }
}
