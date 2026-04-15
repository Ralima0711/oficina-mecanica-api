<?php

namespace App\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'cliente_id' => array_merge($required, ['integer', 'exists:clientes,id']),
            'veiculo_id' => array_merge($required, ['integer', 'exists:veiculos,id']),
            'mecanico_id' => ['sometimes', 'nullable', 'integer', 'exists:mecanicos,id'],
            'descricao_problema' => array_merge($required, ['string', 'max:5000']),
            'diagnostico' => ['sometimes', 'nullable', 'string', 'max:5000'],
            // Status e valor_total sao controlados por regras de negocio e transicoes especificas.
            'status' => ['prohibited'],
            'valor_total' => ['prohibited'],
            'iniciada_em' => ['prohibited'],
            'concluida_em' => ['prohibited'],
        ];
    }
}
