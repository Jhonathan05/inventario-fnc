<?php

namespace App\Http\Requests\SolicitudCambioPassword;

use App\Models\SolicitudCambioPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterSolicitudCambioPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('estado')) {
            $this->merge([
                'estado' => strtolower($this->estado),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'estado' => ['nullable', Rule::in([
                SolicitudCambioPassword::ESTADO_PENDIENTE,
                SolicitudCambioPassword::ESTADO_ATENDIDA,
                SolicitudCambioPassword::ESTADO_RECHAZADA,
            ])],
            'buscar' => ['nullable', 'string', 'max:150'],
            'fecha' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.in' => 'El estado seleccionado no es válido.',
            'buscar.max' => 'El término de búsqueda no puede exceder 150 caracteres.',
            'fecha.date' => 'La fecha de búsqueda no es válida.',
        ];
    }
}
