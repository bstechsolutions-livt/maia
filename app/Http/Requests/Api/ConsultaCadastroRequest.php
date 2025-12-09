<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ConsultaCadastroRequest extends FormRequest
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
        ];
    }
}
