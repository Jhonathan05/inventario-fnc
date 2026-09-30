<?php

namespace App\Http\Requests\SolicitudCambioPassword;

use App\Models\SolicitudCambioPassword;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolicitudCambioPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::exists('users', 'email'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Debe ingresar un correo con formato válido.',
            'email.exists' => 'El correo ingresado no pertenece a ningún usuario registrado.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $email = (string) $this->input('email');

            $userId = User::query()
                ->where('email', $email)
                ->value('id');

            if (! $userId) {
                return;
            }

            $pendiente = SolicitudCambioPassword::query()
                ->where('user_id', $userId)
                ->where('estado', SolicitudCambioPassword::ESTADO_PENDIENTE)
                ->exists();

            if ($pendiente) {
                $validator->errors()->add('email', 'Actualmente ya existe una solicitud pendiente para este usuario.');
            }
        });
    }
}
