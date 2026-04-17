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
        if ($this->isMethod('post')) {
            return [
                'cliente_id' => ['nullable', 'integer', 'exists:clientes,id', 'required_without:cliente_cpf', 'prohibits:cliente_cpf'],
                'cliente_cpf' => ['nullable', 'string', 'max:20', 'required_without:cliente_id', 'prohibits:cliente_id'],
                'veiculo_id' => ['required', 'integer', 'exists:veiculos,id'],
                'mecanico_id' => ['sometimes', 'nullable', 'integer', 'exists:mecanicos,id'],
                'descricao_problema' => ['required', 'string', 'max:5000'],
                'diagnostico' => ['sometimes', 'nullable', 'string', 'max:5000'],
                // Status e valor_total sao controlados por regras de negocio e transicoes especificas.
                'status' => ['prohibited'],
                'valor_total' => ['prohibited'],
                'iniciada_em' => ['prohibited'],
                'concluida_em' => ['prohibited'],
            ];
        }

        return [
            'cliente_id' => ['sometimes', 'integer', 'exists:clientes,id'],
            'veiculo_id' => ['sometimes', 'integer', 'exists:veiculos,id'],
            'mecanico_id' => ['sometimes', 'nullable', 'integer', 'exists:mecanicos,id'],
            'descricao_problema' => ['sometimes', 'string', 'max:5000'],
            'diagnostico' => ['sometimes', 'nullable', 'string', 'max:5000'],
            // Status e valor_total sao controlados por regras de negocio e transicoes especificas.
            'status' => ['prohibited'],
            'valor_total' => ['prohibited'],
            'iniciada_em' => ['prohibited'],
            'concluida_em' => ['prohibited'],
        ];
    }
}
