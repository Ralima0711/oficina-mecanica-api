<?php

namespace App\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CAMADA DE INTERFACE — Form Request
 * Valida e autoriza as requisições HTTP para User.
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email,' . $userId],
            'password' => $this->isMethod('POST') 
                ? ['required', 'string', 'min:6'] 
                : ['nullable', 'string', 'min:6'],
            'role'     => ['nullable', 'in:admin,user,mecanico'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'O nome é obrigatório.',
            'name.max'          => 'O nome não pode ter mais de 255 caracteres.',
            'email.required'    => 'O email é obrigatório.',
            'email.email'       => 'O email deve ser válido.',
            'email.unique'      => 'Este email já está cadastrado no sistema.',
            'password.required' => 'A senha é obrigatória.',
            'password.min'      => 'A senha deve ter pelo menos 6 caracteres.',
            'role.in'           => 'O role deve ser: admin, user ou mecanico.',
        ];
    }
}
