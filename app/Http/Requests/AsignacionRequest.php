<?php

namespace App\Http\Requests;

use App\Models\Asignacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('tipo_accion')) {
            $this->merge([
                'tipo_accion' => strtolower($this->tipo_accion),
            ]);
        }
    }

    public function rules(): array
    {
        $accion = $this->tipo_accion;

        $reglasBase = [
            'equipo_id'   => ['required', 'exists:equipos,id'],
            'tipo_accion' => ['required', Rule::in(array_keys(Asignacion::TIPOS_ACCION))],
            'motivo'      => ['nullable', 'string', 'max:500'],
            'observaciones'=> ['nullable', 'string', 'max:1000'],
            'fecha_accion' => ['nullable', 'date'],
        ];

        // Si es asignación o reemplazo, se requieren datos del usuario
        if (in_array($accion, ['asignacion', 'reemplazo'])) {
            $reglasBase = array_merge($reglasBase, [
                'nombre'               => ['required', 'string', 'max:150'],
                'cedula'               => ['required', 'string', 'max:20'],
                'empresa_propietaria'  => ['nullable', 'string', 'max:150'],
                'dependencia'          => ['nullable', 'string', 'max:150'],
                'fuente_recurso'       => ['nullable', 'string', 'max:150'],
                'empresa_funcionario'  => ['nullable', 'string', 'max:150'],
                'tipo_vinculacion'     => ['nullable', 'string', 'max:100'],
                'shortname'            => ['nullable', 'string', 'max:100'],
                'departamento'         => ['nullable', 'string', 'max:100'],
                'ciudad'               => ['nullable', 'string', 'max:100'],
                'cargo'                => ['nullable', 'string', 'max:100'],
                'area'                 => ['nullable', 'string', 'max:100'],
                'piso'                 => ['nullable', 'string', 'max:20'],
                'distrito'             => ['nullable', 'string', 'max:150'],
                'seccional'            => ['nullable', 'string', 'max:150'],
                'entregado_por'        => ['nullable', 'string', 'max:150'],
            ]);
        }

        // Retiro, baja y mantenimiento usan solo observaciones
        if (in_array($accion, ['retiro', 'baja', 'mantenimiento'], true)) {
            $reglasBase['observaciones'] = ['required', 'string', 'max:1000'];
        }

        return $reglasBase;
    }

    public function messages(): array
    {
        return [
            'equipo_id.required'   => 'El equipo es obligatorio.',
            'equipo_id.exists'     => 'El equipo seleccionado no existe.',
            'tipo_accion.required' => 'El tipo de acción es obligatorio.',
            'tipo_accion.in'       => 'El tipo de acción seleccionado no es válido.',
            'nombre.required'      => 'El nombre del usuario es obligatorio.',
            'cedula.required'      => 'La cédula del usuario es obligatoria.',
            'motivo.required'      => 'El motivo es obligatorio para esta acción.',
            'observaciones.required' => 'Las observaciones son obligatorias para esta acción.',
            'fecha_accion.date'    => 'La fecha de acción debe ser una fecha válida.',
            'distrito.max'         => 'El distrito no puede superar los 150 caracteres.',
            'seccional.max'        => 'La seccional no puede superar los 150 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'equipo_id'    => 'equipo',
            'tipo_accion'  => 'tipo de acción',
            'nombre'       => 'nombre del usuario',
            'cedula'       => 'cédula',
            'distrito'     => 'distrito',
            'seccional'    => 'seccional',
            'entregado_por'=> 'entregado por',
        ];
    }
}
