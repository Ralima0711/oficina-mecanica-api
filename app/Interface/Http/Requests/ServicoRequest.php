<?php

namespace App\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uniqueRule = Rule::unique('servicos', 'codigo');
        $routeId = $this->route('servico') ?? $this->route('id');
        if ($routeId) {
            $uniqueRule->ignore($routeId);
        }

        return [
            'nome'                      => 'required|string|max:150',
            'codigo'                    => ['required', 'string', 'max:50', $uniqueRule],
            'descricao'                 => 'nullable|string|max:500',
            'categoria'                 => 'nullable|string|max:100',
            'preco_base'                => 'required|numeric|min:0',
            'duracao_estimada_minutos'  => 'nullable|integer|min:1',
        ];
    }
}
