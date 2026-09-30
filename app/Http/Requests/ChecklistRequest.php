<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipo_id'       => ['required', 'exists:equipos,id'],
            'orden_trabajo'   => ['nullable', 'string', 'max:100'],
            'observaciones'   => ['nullable', 'string', 'max:1000'],
            'cruce_av'        => ['nullable', 'string', 'max:100'],
            'crece_software'  => ['nullable', 'string', 'max:100'],
            'resultado'       => ['nullable', 'string', 'max:100'],
            'tipo_aprobado'   => ['nullable', 'string', 'max:100'],
            'fnc'             => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'equipo_id.required' => 'Debe seleccionar un equipo.',
            'equipo_id.exists'   => 'El equipo seleccionado no existe.',
        ];
    }
}
