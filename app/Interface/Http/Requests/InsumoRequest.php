<?php

namespace App\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InsumoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'unidade_medida' => ['required', 'string', 'max:20'],
            'preco_unitario' => ['required', 'numeric', 'min:0.01'],
            'estoque_atual' => ['required', 'numeric', 'min:0'],
            'estoque_minimo' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'unidade_medida.required' => 'A unidade de medida é obrigatória.',
            'preco_unitario.required' => 'O preço unitário é obrigatório.',
            'estoque_atual.required' => 'O estoque atual é obrigatório.',
            'estoque_minimo.required' => 'O estoque mínimo é obrigatório.',
        ];
    }
}
