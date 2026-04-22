<?php

namespace App\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmeterOrcamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diagnostico' => ['required', 'string', 'max:5000'],
            'mao_de_obra' => ['required', 'numeric', 'min:0'],
            'pecas' => ['sometimes', 'array'],
            'pecas.*.peca_id' => ['required_with:pecas', 'integer', 'exists:pecas,id'],
            'pecas.*.quantidade' => ['required_with:pecas', 'integer', 'min:1'],
            'insumos' => ['sometimes', 'array'],
            'insumos.*.insumo_id' => ['required_with:insumos', 'integer', 'exists:insumos,id'],
            'insumos.*.quantidade' => ['required_with:insumos', 'numeric', 'gt:0'],
        ];
    }
}
