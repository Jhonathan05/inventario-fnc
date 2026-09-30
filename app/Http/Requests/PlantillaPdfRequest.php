<?php

namespace App\Http\Requests;

use App\Models\PlantillaPdf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlantillaPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'   => ['required', 'string', 'max:150'],
            'tipo'     => ['required', Rule::in(array_keys(PlantillaPdf::TIPOS))],
            'contenido'=> ['required', 'string'],
            'activa'   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'   => 'El nombre de la plantilla es obligatorio.',
            'nombre.max'        => 'El nombre no puede superar los 150 caracteres.',
            'tipo.required'     => 'El tipo de plantilla es obligatorio.',
            'tipo.in'           => 'El tipo de plantilla seleccionado no es válido.',
            'contenido.required'=> 'El contenido de la plantilla es obligatorio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activa' => $this->boolean('activa'),
        ]);

        if ($this->has('tipo')) {
            $this->merge([
                'tipo' => strtolower($this->tipo),
            ]);
        }
    }
}
