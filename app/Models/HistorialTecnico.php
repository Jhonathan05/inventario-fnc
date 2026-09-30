<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistorialTecnico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'historial_tecnicos';

    protected $fillable = [
        'equipo_id',
        'tipo_evento',
        'descripcion',
        'motivo',
        'fecha_evento',
        'usuario_responsable',
        'usuario_asignado_snapshot',
        'archivos',
        'observaciones',
        'user_id',
    ];

    protected $casts = [
        'fecha_evento'              => 'date',
        'usuario_asignado_snapshot' => 'array',
        'archivos'                  => 'array',
    ];

    // ── Constantes de tipos de evento ─────────────────────────────────────────

    const TIPOS_EVENTO_FORM = [
        'requerimiento'             => 'Requerimiento',
        'incidente'                 => 'Incidente',
    ];

    const TIPOS_EVENTO = [
        'requerimiento'             => 'Requerimiento',
        'incidente'                 => 'Incidente',
        'formateo'                  => 'Formateo',
        'cambio_disco'              => 'Cambio de Disco',
        'cambio_ram'                => 'Cambio de RAM',
        'mantenimiento_preventivo'  => 'Mantenimiento Preventivo',
        'mantenimiento_correctivo'  => 'Mantenimiento Correctivo',
        'instalacion_software'      => 'Instalación de Software',
        'limpieza'                  => 'Limpieza',
        'reparacion'                => 'Reparación',
        'observacion'               => 'Observación',
        'otro'                      => 'Otro',
    ];

    const ICONOS_EVENTO = [
        'requerimiento'             => 'bi-life-preserver',
        'incidente'                 => 'bi-exclamation-triangle',
        'formateo'                  => 'bi-hdd',
        'cambio_disco'              => 'bi-hdd-stack',
        'cambio_ram'                => 'bi-memory',
        'mantenimiento_preventivo'  => 'bi-tools',
        'mantenimiento_correctivo'  => 'bi-wrench-adjustable',
        'instalacion_software'      => 'bi-download',
        'limpieza'                  => 'bi-stars',
        'reparacion'                => 'bi-bandaid',
        'observacion'               => 'bi-eye',
        'otro'                      => 'bi-three-dots',
    ];

    const COLORES_EVENTO = [
        'requerimiento'             => 'info',
        'incidente'                 => 'danger',
        'formateo'                  => 'primary',
        'cambio_disco'              => 'info',
        'cambio_ram'                => 'info',
        'mantenimiento_preventivo'  => 'success',
        'mantenimiento_correctivo'  => 'warning',
        'instalacion_software'      => 'primary',
        'limpieza'                  => 'success',
        'reparacion'                => 'danger',
        'observacion'               => 'secondary',
        'otro'                      => 'dark',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getTipoEventoLabelAttribute(): string
    {
        return self::TIPOS_EVENTO[$this->tipo_evento] ?? ucfirst($this->tipo_evento);
    }

    public function getTipoEventoIconoAttribute(): string
    {
        return self::ICONOS_EVENTO[$this->tipo_evento] ?? 'bi-circle';
    }

    public function getTipoEventoColorAttribute(): string
    {
        return self::COLORES_EVENTO[$this->tipo_evento] ?? 'secondary';
    }

    public function getUsuarioResponsableLabelAttribute(): string
    {
        $responsable = trim((string) $this->usuario_responsable);

        if ($responsable === '' || strcasecmp($responsable, 'admin') === 0) {
            return 'Analista TIC';
        }

        return $responsable;
    }
}
