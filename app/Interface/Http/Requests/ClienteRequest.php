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
        if ($this->has('documento')) {
            $this->merge([
                'documento' => preg_replace('/\D/', '', (string) $this->input('documento')),
            ]);
        }
    }

    public function rules(): array
    {
        $tipo = (string) $this->input('tipo');

        return [
            'nome'      => ['required', 'string', 'max:255'],
            'tipo'      => ['required', 'in:pf,pj'],
            'documento' => [
                'required',
                'string',
                $tipo === 'pj' ? 'regex:/^\d{14}$/' : 'regex:/^\d{11}$/',
            ],
            'telefone'  => ['required', 'string', 'max:20'],
            'email'     => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'      => 'O nome é obrigatório.',
            'nome.max'           => 'O nome não pode ter mais de 255 caracteres.',
            'tipo.required'      => 'O tipo de cliente é obrigatório.',
            'tipo.in'            => 'O tipo de cliente deve ser pf ou pj.',
            'documento.required' => 'O documento é obrigatório.',
            'documento.regex'    => 'Documento inválido para o tipo informado.',
            'telefone.required'  => 'O telefone é obrigatório.',
            'telefone.max'       => 'O telefone não pode ter mais de 20 caracteres.',
            'email.required'     => 'O email é obrigatório.',
            'email.email'        => 'O email deve ser válido.',
        ];
    }
}
