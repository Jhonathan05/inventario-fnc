<?php

namespace App\Http\Requests\SolicitudCambioPassword;

use Illuminate\Foundation\Http\FormRequest;

class RejectSolicitudCambioPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observacion' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'observacion.required' => 'La observación es obligatoria para rechazar la solicitud.',
            'observacion.max' => 'La observación no puede exceder los 1000 caracteres.',
        ];
    }
}
