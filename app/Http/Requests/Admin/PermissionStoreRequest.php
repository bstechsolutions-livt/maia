<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PermissionStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:permissions,slug', 'regex:/^[a-z0-9-_.]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'group' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome da permissão é obrigatório.',
            'name.unique' => 'Já existe uma permissão com este nome.',
            'slug.required' => 'O slug da permissão é obrigatório.',
            'slug.unique' => 'Já existe uma permissão com este slug.',
            'slug.regex' => 'O slug deve conter apenas letras minúsculas, números, hífens, underlines e pontos.',
            'group.required' => 'O grupo da permissão é obrigatório.',
        ];
    }
}
