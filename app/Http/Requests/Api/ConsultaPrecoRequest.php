<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ConsultaPrecoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'codauxiliar' => ['required', 'numeric', 'digits_between:1,20'],
            'cpf' => ['sometimes', 'nullable', 'string', 'max:20'],
            'numregiao' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codauxiliar.required' => 'O código auxiliar (EAN) é obrigatório.',
            'codauxiliar.numeric' => 'O código auxiliar (EAN) deve ser numérico.',
            'codauxiliar.digits_between' => 'O código auxiliar (EAN) deve ter entre 1 e 20 dígitos.',
            'cpf.string' => 'O CPF/CNPJ deve ser uma string.',
            'cpf.max' => 'O CPF/CNPJ deve ter no máximo 20 caracteres.',
            'numregiao.integer' => 'O número da região deve ser um número inteiro.',
        ];
    }
}
