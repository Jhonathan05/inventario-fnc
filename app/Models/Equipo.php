<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $responsable_cedula
 * @property string|null $responsable_nombre
 * @property string|null $responsable_cargo
 * @property string|null $responsable_ciudad
 * @property string|null $responsable_area
 * @property string|null $responsable_tipo_recurso
 * @property \Illuminate\Support\Carbon|null $fecha_inicio_responsable
 * @property \Illuminate\Support\Carbon|null $fecha_fin_responsable
 */
class Equipo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'equipos';

    protected $fillable = [
        'tipo_recurso_id',
        'serial',
        'activo_fijo',
        'placa',
        'marca',
        'modelo',
        'nombre_equipo',
        'estado_operativo',
        'razon_estado',
        'procesador',
        'ram',
        'disco',
        'sistema_operativo',
        'fecha_compra',
        'fin_garantia',
        'tiempo_uso',
        'estado_uso',
        'administrado',
        'tipo_propiedad',
        'checklist_responsable',
        'orden_remision',
        'observaciones',
        'responsable_cedula',
        'responsable_nombre',
        'responsable_cargo',
        'responsable_ciudad',
        'responsable_area',
        'responsable_tipo_recurso',
        'fecha_inicio_responsable',
        'fecha_fin_responsable',
        'consecutivo',
        'fila_cmdb',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($equipo) {
            if (empty($equipo->consecutivo)) {
                // withTrashed() asegura que no haya colisiones si el último activo 
                // con el consecutivo más alto fue eliminado lógicamente.
                $maxConsecutivo = static::withTrashed()->max('consecutivo');
                $equipo->consecutivo = $maxConsecutivo ? $maxConsecutivo + 1 : 1;
            }
        });
    }

    protected $casts = [
        'fecha_compra' => 'date',
        'fin_garantia' => 'date',
        'fecha_inicio_responsable' => 'date',
        'fecha_fin_responsable' => 'date',
    ];

    public function tipoRecurso(): BelongsTo
    {
        return $this->belongsTo(TipoRecurso::class);
    }

    public function usuarioAsignado(): HasOne
    {
        return $this->hasOne(UsuarioAsignado::class);
    }

    public function periferico(): HasOne
    {
        return $this->hasOne(Periferico::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    public function complementos(): HasMany
    {
        return $this->hasMany(ActivoComplemento::class, 'equipo_id');
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }
    
    public function prestamoVigente()
    {
        return $this->hasOne(Prestamo::class)
            ->whereIn('estado', ['Pendiente', 'Activo', 'Vencido'])
            ->latest();
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function latestChecklist(): HasOne
    {
        return $this->hasOne(Checklist::class)->latestOfMany();
    }

    public function historialTecnico(): HasMany
    {
        return $this->hasMany(HistorialTecnico::class)->orderByDesc('fecha_evento');
    }

    public function historialAdministrativo(): HasMany
    {
        return $this->hasMany(HistorialAdministrativo::class)->orderByDesc('created_at');
    }

    public function licenciaAsignaciones(): HasMany
    {
        return $this->hasMany(LicenciaAsignacion::class, 'equipo_id');
    }

    /**
     * Indica si el equipo tiene un usuario asignado actualmente.
     */
    public function estaAsignado(): bool
    {
        return $this->usuarioAsignado()->exists();
    }

    public function estaDisponibleParaPrestamo(): bool
    {
        // Regla OBLIGATORIA: Validar que el activo:
        // - Existe (implícito al llamar al método en un modelo instanciado)
        // - Está en bodega disponible para asignar (catálogo real:
        //   almacenado + razones de bodega; antes estado 'disponible')
        // - NO tiene una asignación incompatible vigente (estaAsignado)
        // - NO tiene otro préstamo activo vigente
        return $this->estado_operativo === 'almacenado'
            && in_array($this->razon_estado, \App\Domain\Activos\RazonEstado::EN_BODEGA, true)
            && !$this->estaAsignado()
            && !$this->prestamos()->whereIn('estado', ['Pendiente', 'Activo', 'Vencido'])->exists();
    }

    public function camposPersonalizadosValores(): HasMany
    {
        return $this->hasMany(CampoPersonalizadoValor::class, 'entidad_id');
    }

    /**
     * Etiqueta legible del estado operativo (catálogo real del CMDB).
     */
    public function getEstadoLabelAttribute(): string
    {
        return \App\Domain\Activos\EstadoOperativo::label($this->estado_operativo);
    }

    /**
     * Tono del design system para el badge de estado.
     */
    public function getEstadoBadgeAttribute(): string
    {
        return \App\Domain\Activos\EstadoOperativo::tone($this->estado_operativo);
    }

    /**
     * Etiqueta legible de la razón del estado (catálogo real del CMDB).
     */
    public function getRazonLabelAttribute(): string
    {
        return \App\Domain\Activos\RazonEstado::label($this->razon_estado);
    }

    /* ── Scopes del catálogo real (Lote B) ─────────────────────────────── */

    /** En bodega (independiente de la razón). */
    public function scopeEnBodega($query)
    {
        return $query->where('estado_operativo', 'almacenado');
    }

    /** En bodega y disponible para asignar (razones de bodega del catálogo). */
    public function scopeDisponiblesParaAsignar($query)
    {
        return $query->where('estado_operativo', 'almacenado')
            ->whereIn('razon_estado', \App\Domain\Activos\RazonEstado::EN_BODEGA);
    }

    /** Prestado (en operación por razón PRÉSTAMO). */
    public function scopeEnPrestamo($query)
    {
        return $query->where('estado_operativo', 'en_operacion')
            ->where('razon_estado', 'prestamo');
    }

    /** En el taller: alistamiento/soporte en bodega o devuelto a proveedor. */
    public function scopeEnTaller($query)
    {
        return $query->where(function ($w) {
            $w->where(function ($x) {
                $x->where('estado_operativo', 'almacenado')
                  ->where('razon_estado', 'alistamiento_soporte');
            })->orWhere(function ($x) {
                $x->where('estado_operativo', 'transito')
                  ->where('razon_estado', 'devuelto_a_proveedor');
            });
        });
    }

    /**
     * Identificador interno estandarizado generado dinámicamente.
     */
    public function getIdentificadorInternoAttribute(): string
    {
        $prefijo = $this->tipoRecurso ? $this->tipoRecurso->prefijo : 'ACT';
        // Formatea el ID rellenando con ceros a la izquierda hasta 4 dígitos (Ej. LAP-0015)
        $numero = $this->consecutivo ?? $this->id;
        return $prefijo . '-' . str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }

    public function getSerialVisualAttribute(): string
    {
        $serialReal = trim((string) $this->serial);
        $invalidos = ['PENDIENTE', 'N/A', 'NA', 'NO TIENE', 'SIN SERIAL', 'SIN REGISTRO'];
        
        if ($serialReal === '' || str_starts_with(strtoupper($serialReal), 'SIN_SERIAL_') || in_array(strtoupper($serialReal), $invalidos, true)) {
            $prefijo = $this->tipoRecurso ? $this->tipoRecurso->prefijo : 'ACT';
            $numero = $this->consecutivo ?? $this->id;
            $codigo = str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
            return "{$prefijo}-S{$codigo}";
        }
        return $serialReal;
    }

    /**
     * Identificador visual inteligente para la placa.
     */
    public function getPlacaVisualAttribute(): string
    {
        $placaReal = trim((string) $this->placa);
        $invalidos = ['PENDIENTE', 'N/A', 'NA', 'NO TIENE', 'SIN PLACA', 'SIN REGISTRO'];
        
        if ($placaReal === '' || in_array(strtoupper($placaReal), $invalidos, true)) {
            $prefijo = $this->tipoRecurso ? $this->tipoRecurso->prefijo : 'ACT';
            $numero = $this->consecutivo ?? $this->id;
            $codigo = str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
            return "{$prefijo}-P{$codigo}";
        }
        return $placaReal;
    }

    /**
     * Calcula o sanea el tiempo de uso para evitar mostrar fórmulas de Excel.
     */
    public function getTiempoUsoAttribute($value)
    {
        $valueStr = trim((string) $value);
        if ($valueStr === '' || str_starts_with($valueStr, '=') || strtoupper($valueStr) === 'PENDIENTE') {
            if ($this->fecha_compra) {
                $diff = $this->fecha_compra->diff(\Carbon\Carbon::now());
                if ($diff->y > 0) {
                    return $diff->y . ($diff->y === 1 ? ' año' : ' años') . 
                           ($diff->m > 0 ? ', ' . $diff->m . ($diff->m === 1 ? ' mes' : ' meses') : '');
                } elseif ($diff->m > 0) {
                    return $diff->m . ($diff->m === 1 ? ' mes' : ' meses');
                } else {
                    return 'Menos de 1 mes';
                }
            }
            return 'Pendiente / No definido';
        }
        return $value;
    }

    /**
     * Nombre legible del activo: si el CMDB trae un placeholder (PENDIENTE,
     * SIN NOMBRE…), se usa el identificador interno para no mostrar datos
     * falsos en las listas y en el modal de corrección.
     */
    public function getNombreVisibleAttribute(): string
    {
        $nombre = trim((string) $this->nombre_equipo);
        $placeholders = ['', 'PENDIENTE', 'SIN NOMBRE', 'N/A', 'NA', 'SIN ASIGNAR', 'NO APLICA', '-'];

        return in_array(mb_strtoupper($nombre), $placeholders, true)
            ? $this->identificador_interno
            : $nombre;
    }

    /**
     * Ficha completa del activo para el modal de Correcciones.
     *
     * Devuelve secciones con etiqueta => valor. Un valor null/vacío se
     * representa como «—» en la vista; el objetivo es que quien corrige sepa
     * exactamente de qué equipo se trata (incluida su fila del CMDB).
     *
     * @return array<string, array<string, string|null>|null>
     */
    public function fichaParaCorreccion(): array
    {
        $custodio = $this->usuarioAsignado;

        return [
            'identificacion' => [
                'Activo'            => $this->identificador_interno,
                'Fila del CMDB'     => $this->fila_cmdb ? 'Fila ' . $this->fila_cmdb : null,
                'Nombre de equipo'  => $this->nombre_equipo,
                'Serial'            => $this->serial_visual,
                'Placa'             => $this->placa_visual,
                'Activo fijo'       => $this->activo_fijo,
                'Tipo de recurso'   => $this->tipoRecurso?->nombre,
                'Marca'             => $this->marca,
                'Modelo'            => $this->modelo,
            ],
            'estado' => [
                'Estado operativo'  => $this->estado_label,
                'Razón del estado'  => $this->razon_label,
                'Estado de uso'     => $this->estado_uso,
                'Administrado'      => $this->administrado,
                'Tipo de propiedad' => $this->tipo_propiedad,
            ],
            'tecnica' => [
                'Procesador'        => $this->procesador,
                'Memoria RAM'       => $this->ram,
                'Disco'             => $this->disco,
                'Sistema operativo' => $this->sistema_operativo,
                'Fecha de compra'   => $this->fecha_compra?->format('d/m/Y'),
                'Fin de garantía'   => $this->fin_garantia?->format('d/m/Y'),
                'Tiempo de uso'     => $this->tiempo_uso,
            ],
            'cmdb' => [
                'Orden de remisión'      => $this->orden_remision,
                'Checklist (responsable TI)' => $this->checklist_responsable,
                'Observaciones'          => $this->observaciones,
                'Responsable CMDB'       => $this->responsable_nombre,
                'Cédula responsable'     => $this->responsable_cedula,
                'Cargo responsable'      => $this->responsable_cargo,
                'Ciudad responsable'     => $this->responsable_ciudad,
                'Área responsable'       => $this->responsable_area,
                'Inicio responsabilidad' => $this->fecha_inicio_responsable?->format('d/m/Y'),
                'Fin responsabilidad'    => $this->fecha_fin_responsable?->format('d/m/Y'),
            ],
            'custodia' => $custodio ? [
                'Custodio'    => $custodio->nombre,
                'Cédula'      => $custodio->cedula,
                'Cargo'       => $custodio->cargo,
                'Área'        => $custodio->area,
                'Ciudad'      => $custodio->ciudad,
                'Departamento'=> $custodio->departamento,
                'Distrito'    => $custodio->distrito,
                'Seccional'   => $custodio->seccional,
                'Dependencia' => $custodio->dependencia,
                'Modalidad'   => $custodio->modalidad,
                'Desde'       => $custodio->desde?->format('d/m/Y'),
            ] : null,
        ];
    }
}
