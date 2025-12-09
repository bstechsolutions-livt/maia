<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ConsultaEstoqueRequest extends FormRequest
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
            'codfilial' => ['sometimes', 'nullable', 'integer'],
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
            'codfilial.integer' => 'O código da filial deve ser um número inteiro.',
        ];
    }
}
