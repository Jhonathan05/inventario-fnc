<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Licencia;

class StoreLicenciaAsignacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'funcionario_id' => 'required|exists:funcionarios,id',
            'equipo_id' => 'required|exists:equipos,id',
            'licencia_id' => 'nullable|exists:licencias,id',
            'fecha_asignacion' => 'required|date',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_asignacion',
            'correo_activacion' => 'nullable|email|max:255',
            'estado' => 'required|in:Activa,Vencida,Retirada,Suspendida',
            'observaciones' => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $licenciaId = $this->input('licencia_id');
            if ($licenciaId && $this->input('estado') === 'Activa') {
                $licencia = Licencia::find($licenciaId);
                if ($licencia && $licencia->cupos_disponibles <= 0) {
                    $validator->errors()->add('licencia_id', 'Esta licencia ya alcanzó el máximo de asignaciones permitidas.');
                }
            }
        });
    }
}
