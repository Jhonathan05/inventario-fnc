<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoRecursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipoId = $this->route('tipo_recurso')?->id;

        return [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('tipo_recursos', 'nombre')->ignore($tipoId)],
            'complementos_ids'   => ['nullable', 'array'],
            'complementos_ids.*' => ['exists:catalogo_complementos,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del tipo de recurso es obligatorio.',
            'nombre.unique'   => 'Ya existe un tipo de recurso con este nombre.',
            'nombre.max'      => 'El nombre no puede superar los 100 caracteres.',
        ];
    }
}
