<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAsignacionResponsabilidadRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'responsable_id' => 'required|integer',
            'responsable_nombre' => 'required|string|max:150',
            'responsable_cedula' => 'required|string|max:50',
            'tipo_usuario' => 'required|string|max:50',
            'nombre_usuario' => 'required|string|max:100',
            'documento' => 'nullable|string|max:50',
            'empresa' => 'nullable|string|max:100',
            'cargo' => 'nullable|string|max:100',
            'proyecto' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:100',
            'correo' => 'nullable|email|max:100',
            'telefono' => 'nullable|string|max:20',
            'fecha_inicio' => 'required|date',
            'fecha_final_estimada' => 'nullable|date|after_or_equal:fecha_inicio',
            'observaciones' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'fecha_final_estimada.after_or_equal' => 'La fecha final estimada debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}
