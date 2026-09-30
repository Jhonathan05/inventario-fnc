<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Equipo;

class StoreAsignacionResponsabilidadRequest extends FormRequest
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
    
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $equipo = $this->route('equipo');
            
            if (!$equipo) {
                return;
            }

            // Validar que el equipo no tenga otra asignación bajo responsabilidad activa
            if ($equipo->asignacionResponsabilidadActiva()->exists()) {
                $validator->errors()->add('general', 'El equipo ya tiene una Asignación Bajo Responsabilidad activa.');
            }

            // Validar que el equipo no tenga una asignación normal (Funcionario Asignado) activa
            if ($equipo->usuarioAsignado()->exists()) {
                $validator->errors()->add('general', 'El equipo tiene un Funcionario Asignado. No pueden coexistir ambas modalidades. Retire el funcionario primero.');
            }

            // Validar autorización del Responsable Administrativo
            $responsable_cedula = $this->input('responsable_cedula');
            if ($responsable_cedula) {
                $tieneAutorizacion = \App\Models\AutorizacionActivo::query()
                    ->where('cedula', $responsable_cedula)
                    ->where('estado', \App\Models\AutorizacionActivo::ESTADO_CARGADA)
                    ->exists();

                if (!$tieneAutorizacion) {
                    $validator->errors()->add('responsable_id', 'El funcionario seleccionado no tiene autorización vigente disponible para recibir este activo.');
                }
            }
        });
    }
}
