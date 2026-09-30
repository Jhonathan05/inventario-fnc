<?php

namespace App\Http\Requests;

use App\Models\HistorialTecnico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HistorialTecnicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('tipo_evento')) {
            $this->merge([
                'tipo_evento' => strtolower($this->tipo_evento),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'equipo_id'          => ['required', 'exists:equipos,id'],
            'tipo_evento'        => ['required', Rule::in(array_keys(HistorialTecnico::TIPOS_EVENTO_FORM))],
            'descripcion'        => ['nullable', 'string', 'max:500'],
            'motivo'             => ['nullable', 'string', 'max:500'],
            'fecha_evento'       => ['required', 'date', 'before_or_equal:today'],
            'usuario_responsable'=> ['required', 'string', 'max:150'],
            'observaciones'      => ['required', 'string', 'max:2000'],
            'archivos'           => ['nullable', 'array', 'max:5'],
            'archivos.*'         => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'equipo_id.required'           => 'El equipo es obligatorio.',
            'equipo_id.exists'             => 'El equipo seleccionado no existe.',
            'tipo_evento.required'         => 'El tipo de evento es obligatorio.',
            'tipo_evento.in'               => 'El tipo de evento seleccionado no es válido.',
            'descripcion.max'              => 'La descripción no puede superar los 500 caracteres.',
            'observaciones.required'       => 'Las observaciones son obligatorias.',
            'fecha_evento.required'        => 'La fecha del evento es obligatoria.',
            'fecha_evento.date'            => 'La fecha del evento debe ser una fecha válida.',
            'fecha_evento.before_or_equal' => 'La fecha del evento no puede ser futura.',
            'usuario_responsable.required' => 'El nombre del técnico responsable es obligatorio.',
            'usuario_responsable.max'      => 'El nombre del responsable no puede superar los 150 caracteres.',
            'archivos.max'                 => 'Se permiten como máximo 5 archivos adjuntos.',
            'archivos.*.mimes'             => 'Los archivos deben ser PDF, imágenes o documentos Word.',
            'archivos.*.max'               => 'Cada archivo no puede superar los 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'equipo_id'          => 'equipo',
            'tipo_evento'        => 'tipo de evento',
            'descripcion'        => 'descripción',
            'fecha_evento'       => 'fecha del evento',
            'usuario_responsable'=> 'técnico responsable',
        ];
    }
}
