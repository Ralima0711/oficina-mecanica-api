<?php

namespace App\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PecaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdating = (bool) $this->route('peca');

        return [
            'nome' => ['required', 'string', 'max:255'],
            'codigo' => [
                'required',
                'string',
                'max:255',
                $isUpdating
                    ? Rule::unique('pecas', 'codigo')->ignore($this->route('peca'))
                    : 'unique:pecas,codigo',
            ],
            'categoria' => ['nullable', 'string', 'max:255'],
            'preco_unitario' => ['required', 'numeric', 'min:0.01'],
            'estoque_atual' => ['required', 'integer', 'min:0'],
            'estoque_minimo' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'codigo.required' => 'O código é obrigatório.',
            'codigo.unique' => 'Já existe uma peça com esse código.',
            'preco_unitario.required' => 'O preço unitário é obrigatório.',
            'estoque_atual.required' => 'O estoque atual é obrigatório.',
            'estoque_minimo.required' => 'O estoque mínimo é obrigatório.',
        ];
    }
}
