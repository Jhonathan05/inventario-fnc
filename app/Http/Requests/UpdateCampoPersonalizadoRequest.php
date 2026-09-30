<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCampoPersonalizadoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('campos_personalizados.editar');
    }

    protected function failedAuthorization()
    {
        throw new \Illuminate\Auth\Access\AuthorizationException('No tiene permisos para editar campos personalizados.');
    }

    protected function prepareForValidation()
    {
        if ($this->has('tipo')) {
            $this->merge([
                'tipo' => strtolower($this->tipo),
            ]);
        }
        if ($this->has('modulo')) {
            $this->merge([
                'modulo' => strtolower($this->modulo),
            ]);
        }
        if ($this->has('posicion_grilla_despues_de') && $this->posicion_grilla_despues_de) {
            $this->merge([
                'posicion_grilla_despues_de' => strtolower($this->posicion_grilla_despues_de),
            ]);
        }
        if ($this->has('exportar_excel_despues_de') && $this->exportar_excel_despues_de) {
            $this->merge([
                'exportar_excel_despues_de' => strtolower($this->exportar_excel_despues_de),
            ]);
        }
        if ($this->has('modo_asignacion_masiva') && $this->modo_asignacion_masiva) {
            $this->merge([
                'modo_asignacion_masiva' => strtolower($this->modo_asignacion_masiva),
            ]);
        }

        $tipo = strtolower($this->tipo ?? '');

        // Si el tipo no es select/multiselect, eliminamos los campos de asignación masiva
        if (!in_array($tipo, ['select', 'multiselect'])) {
            $this->request->remove('modo_asignacion_masiva');
            $this->request->remove('valor_inicial_masivo');
            $this->request->remove('asignar_valor_inicial');
            $this->request->remove('opciones');
        } else {
            // Si es select/multiselect pero NO se marcó "asignar_valor_inicial", eliminamos los valores que viajan ocultos
            if (!$this->boolean('asignar_valor_inicial')) {
                $this->request->remove('modo_asignacion_masiva');
                $this->request->remove('valor_inicial_masivo');
            }
        }
    }

    public function rules(): array
    {
        $id = $this->route('campos_personalizado') ?? $this->route('campo_personalizado');
        $rules = [
            'modulo'               => 'required|string|max:50',
            'nombre'               => 'required|string|max:150|unique:campos_personalizados,nombre,' . $id,
            'descripcion'          => 'nullable|string',
            'tipo'                 => 'required|string|in:texto,textarea,numero,fecha,correo,telefono,boolean,select,multiselect,url,archivo',
            'obligatorio'          => 'boolean',
            'editable'             => 'boolean',
            'visible'              => 'boolean',
            'importable'           => 'boolean',
            'exportable'           => 'boolean',
            'exportar_por_defecto' => 'boolean',
            'activo'               => 'boolean',
            'opciones'             => 'nullable|string',
            'mostrar_en_grilla'    => 'boolean',
            'participa_exportacion_cmdb' => 'boolean',
            'participa_exportacion_completa' => 'boolean',
            'participa_reportes'   => 'boolean',
            'participa_filtros'    => 'boolean',
            'posicion_grilla_despues_de' => 'nullable|string',
            'exportar_excel_despues_de'  => 'nullable|string',
            'asignar_valor_inicial' => 'boolean',
            'modo_asignacion_masiva' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['solo_vacios', 'sobrescribir_todos'])],
            'valor_inicial_masivo' => 'nullable|string',
        ];

        $tipo = strtolower($this->tipo ?? '');

        if (!in_array($tipo, ['select', 'multiselect'])) {
            unset($rules['modo_asignacion_masiva']);
            unset($rules['valor_inicial_masivo']);
            unset($rules['opciones']);
            unset($rules['asignar_valor_inicial']);
        } else {
            if (!$this->boolean('asignar_valor_inicial')) {
                unset($rules['modo_asignacion_masiva']);
                unset($rules['valor_inicial_masivo']);
            }
        }

        return $rules;
    }
}
