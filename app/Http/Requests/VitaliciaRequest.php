<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VitaliciaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nombre' => 'required|string|max:255',
            'fabricante' => 'nullable|string|max:255',
            'tipo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'cantidad_comprada' => 'required|integer|min:1',
            'fecha_compra' => 'nullable|date',
            'numero_factura' => 'nullable|string|max:255',
            'estado' => 'required|in:Activa,Inactiva',
            'observaciones' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'cantidad_comprada.required' => 'La cantidad comprada es obligatoria.',
            'cantidad_comprada.min' => 'La cantidad mínima es 1.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ];
    }
}
