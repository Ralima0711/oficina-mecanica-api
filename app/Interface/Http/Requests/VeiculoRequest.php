<?php

namespace App\Interface\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VeiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('placa')) {
            $this->merge([
                'placa' => strtoupper(str_replace(' ', '', trim($this->input('placa')))),
            ]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Os dados fornecidos são inválidos.',
            'errors'  => $validator->errors(),
        ], 422));
    }

    public function rules(): array
    {
        $veiculoParam = $this->route('veiculo') ?? $this->route('id');
        $veiculoId = is_object($veiculoParam) ? $veiculoParam->id : $veiculoParam;
        $placaUniqueRule = 'unique:veiculos,placa' . ($veiculoId ? ',' . $veiculoId : '');

        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'placa'      => ['required', 'string', 'max:10', 'regex:/^[A-Z]{3}-?\d{4}$/', $placaUniqueRule],
            'marca'      => ['required', 'string', 'max:255'],
            'modelo'     => ['required', 'string', 'max:255'],
            'ano'        => ['required', 'integer'],
            'cor'        => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'O cliente é obrigatório.',
            'cliente_id.integer'  => 'O cliente deve ser um identificador válido.',
            'cliente_id.exists'   => 'O cliente informado não existe.',
            'placa.required'      => 'A placa é obrigatória.',
            'placa.max'           => 'A placa não pode ter mais de 10 caracteres.',
            'placa.regex'         => 'Placa inválida.',
            'placa.unique'        => 'Esta placa já está cadastrada.',
            'marca.required'      => 'A marca é obrigatória.',
            'marca.max'           => 'A marca não pode ter mais de 255 caracteres.',
            'modelo.required'     => 'O modelo é obrigatório.',
            'modelo.max'          => 'O modelo não pode ter mais de 255 caracteres.',
            'ano.required'        => 'O ano é obrigatório.',
            'ano.integer'         => 'O ano deve ser um número inteiro.',
            'cor.max'             => 'A cor não pode ter mais de 50 caracteres.',
        ];
    }
}
