<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLicenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipo = $this->input('tipo_licencia');

        $rules = [
            'nombre' => 'required|string|max:255|unique:licencias,nombre,' . $this->licencia->id,
            'tipo_licencia' => 'required|in:Suscripción,Vitalicia',
            'estado' => 'required|in:Activa,Suspendida,Vencida',
            'fecha_compra' => 'nullable|date',
            'fecha_renovacion' => 'nullable|date|after_or_equal:fecha_inicio',
            'correo_compra' => 'nullable|email|max:255',
            'observaciones' => 'nullable|string',
        ];

        if ($tipo === 'Suscripción') {
            $rules['cantidad_maxima'] = 'required|integer|min:1';
            $rules['fecha_inicio'] = 'nullable|date';
            $rules['fecha_vencimiento'] = 'nullable|date|after_or_equal:fecha_inicio';
            $rules['requiere_correo'] = 'required|boolean';
            
            if ($this->input('requiere_correo')) {
                $rules['correo_asociado'] = 'required|email|max:255';
            } else {
                $rules['correo_asociado'] = 'nullable|email|max:255';
            }
        } else if ($tipo === 'Vitalicia') {
            $rules['cantidad_maxima'] = 'nullable|integer|min:1';
            $rules['correo_asociado'] = 'required|email|max:255';
            $rules['requiere_correo'] = 'nullable|boolean';
            $rules['fecha_inicio'] = 'nullable|date';
            $rules['fecha_vencimiento'] = 'nullable|date';
        }

        return $rules;
    }

    protected function prepareForValidation()
    {
        if ($this->has('requiere_correo')) {
            $this->merge([
                'requiere_correo' => filter_var($this->requiere_correo, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
        
        if ($this->tipo_licencia === 'Vitalicia') {
            $this->merge([
                'cantidad_maxima' => 1,
            ]);
        }
    }
}
