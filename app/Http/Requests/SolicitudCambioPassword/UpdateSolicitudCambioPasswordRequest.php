<?php

namespace App\Http\Requests\SolicitudCambioPassword;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSolicitudCambioPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'observacion.max' => 'La observación no puede exceder los 1000 caracteres.',
        ];
    }
}
