<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CriarPedidoRequest extends FormRequest
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
            'cpf' => ['required', 'string', 'max:20'],
            'codtransp' => ['required', 'integer'],
            'codfilial' => ['sometimes', 'nullable', 'integer'],
            'numregiao' => ['sometimes', 'nullable', 'integer'],
            'obs' => ['sometimes', 'nullable', 'string', 'max:500'],
            'obs_entrega' => ['sometimes', 'nullable', 'string', 'max:500'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.codauxiliar' => ['required', 'numeric', 'digits_between:1,20'],
            'itens.*.quantidade' => ['required', 'numeric', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cpf.required' => 'O CPF/CNPJ do cliente é obrigatório.',
            'cpf.string' => 'O CPF/CNPJ deve ser uma string.',
            'cpf.max' => 'O CPF/CNPJ deve ter no máximo 20 caracteres.',
            'codtransp.required' => 'O código da transportadora é obrigatório.',
            'codtransp.integer' => 'O código da transportadora deve ser um número inteiro.',
            'codfilial.integer' => 'O código da filial deve ser um número inteiro.',
            'numregiao.integer' => 'O número da região deve ser um número inteiro.',
            'obs.string' => 'A observação deve ser uma string.',
            'obs.max' => 'A observação deve ter no máximo 500 caracteres.',
            'obs_entrega.string' => 'A observação de entrega deve ser uma string.',
            'obs_entrega.max' => 'A observação de entrega deve ter no máximo 500 caracteres.',
            'itens.required' => 'É necessário informar pelo menos um item.',
            'itens.array' => 'Os itens devem ser um array.',
            'itens.min' => 'É necessário informar pelo menos um item.',
            'itens.*.codauxiliar.required' => 'O código auxiliar (EAN) do item é obrigatório.',
            'itens.*.codauxiliar.numeric' => 'O código auxiliar (EAN) do item deve ser numérico.',
            'itens.*.codauxiliar.digits_between' => 'O código auxiliar (EAN) deve ter entre 1 e 20 dígitos.',
            'itens.*.quantidade.required' => 'A quantidade do item é obrigatória.',
            'itens.*.quantidade.numeric' => 'A quantidade do item deve ser numérica.',
            'itens.*.quantidade.min' => 'A quantidade do item deve ser no mínimo 1.',
        ];
    }
}
