<?php

namespace App\Services;

use App\Models\Acta;
use App\Models\Asignacion;
use App\Models\Equipo;
use App\Models\Funcionario;
use App\Models\UsuarioAsignado;
use Illuminate\Support\Facades\DB;

/**
 * CustodiaService — único escritor de la custodia actual del activo.
 *
 * Lote A ("custodia única y acta que sí asigna", plan §11):
 *  - `usuario_asignados` es la PROYECCIÓN de custodia actual (1 fila por
 *    equipo) y `equipos.responsable_*` su snapshot desnormalizado.
 *  - `asignaciones` es el historial inmutable de movimientos; el ítem que
 *    abre una custodia queda `vigente = true` y se cierra con `fecha_fin`.
 *  - Todo pasa dentro de la transacción del acta: o se registra todo o nada.
 *
 * Modalidades (plan D1): `asignado` cuenta para la regla "un computador por
 * funcionario"; `custodia_fisica` es tenencia (bodega) sin asignación.
 */
class CustodiaService
{
    /**
     * Campos del snapshot de custodia que se proyectan a usuario_asignados.
     * Clave = columna de usuario_asignados, valor = clave del snapshot.
     */
    private const MAPA_SNAPSHOT = [
        'nombre'              => 'usuario_nombre',
        'cedula'              => 'usuario_cedula',
        'empresa_propietaria' => 'usuario_empresa_propietaria',
        'dependencia'         => 'usuario_dependencia',
        'fuente_recurso'      => 'usuario_fuente_recurso',
        'empresa_funcionario' => 'usuario_empresa_funcionario',
        'tipo_vinculacion'    => 'usuario_tipo_vinculacion',
        'shortname'           => 'usuario_shortname',
        'departamento'        => 'usuario_departamento',
        'ciudad'              => 'usuario_ciudad',
        'cargo'               => 'usuario_cargo',
        'area'                => 'usuario_area',
        'piso'                => 'usuario_piso',
        'distrito'            => 'usuario_distrito',
        'seccional'           => 'usuario_seccional',
    ];

    /**
     * Abre la custodia del activo con el snapshot del acta.
     *
     * Cierra (marca `fecha_fin` + `vigente=false` en su ítem y elimina la fila)
     * cualquier custodia anterior, crea la nueva fila en `usuario_asignados`
     * y sincroniza el snapshot `responsable_*` del equipo.
     *
     * @param array<string, mixed> $snapshot Campos usuario_* del acta/ítem.
     */
    public function abrir(
        Equipo $equipo,
        array $snapshot,
        ?Acta $acta = null,
        ?Asignacion $item = null,
        ?Funcionario $funcionario = null,
        string $modalidad = 'asignado'
    ): UsuarioAsignado {
        // 1. Cerrar la custodia previa (si existe) sin tocar el nuevo ciclo.
        $this->cerrar($equipo, motivo: 'nueva custodia');

        // 2. Nueva proyección de custodia actual.
        $datos = ['equipo_id' => $equipo->id];
        foreach (self::MAPA_SNAPSHOT as $columna => $clave) {
            if (array_key_exists($clave, $snapshot) && $snapshot[$clave] !== null) {
                $datos[$columna] = $snapshot[$clave];
            }
        }
        if ($acta !== null) $datos['acta_id'] = $acta->id;
        if ($funcionario !== null) $datos['funcionario_id'] = $funcionario->id;
        $datos['desde'] = now();
        $datos['hasta'] = null;
        $datos['modalidad'] = $modalidad;

        $ua = UsuarioAsignado::create($datos);

        // 3. El ítem del acta queda como custodia vigente.
        if ($item !== null) {
            $item->update(['vigente' => true, 'modalidad' => $modalidad, 'fecha_fin' => null]);
        }

        // 4. Snapshot desnormalizado del equipo (listados/reportes).
        $this->sincronizarSnapshot($equipo, $ua);

        return $ua;
    }

    /**
     * Cierra la custodia actual del activo: marca el ítem vigente, elimina
     * la proyección y limpia el snapshot del equipo. El historial vive en
     * `asignaciones` (nunca se borra).
     */
    public function cerrar(Equipo $equipo, string $motivo = ''): ?UsuarioAsignado
    {
        $ua = $equipo->usuarioAsignado()->first();

        if ($ua === null) {
            return null;
        }

        // 1. Cerrar el ítem que esta custodia mantenía vigente.
        Asignacion::where('equipo_id', $equipo->id)
            ->where('vigente', true)
            ->update(['vigente' => false, 'fecha_fin' => now()]);

        // 2. Retirar la proyección actual.
        $ua->hasta = now();
        $ua->save();
        $ua->delete();

        // 3. Limpiar el snapshot del equipo.
        $equipo->update([
            'responsable_cedula' => null,
            'responsable_nombre' => null,
            'responsable_cargo'  => null,
            'responsable_ciudad' => null,
            'responsable_area'   => null,
        ]);

        return $ua;
    }

    /** Custodia actual del activo (proyección), o null. */
    public function custodioActual(Equipo $equipo): ?UsuarioAsignado
    {
        return $equipo->usuarioAsignado()->first();
    }

    /**
     * Sincroniza el snapshot `responsable_*` del equipo desde la custodia.
     */
    public function sincronizarSnapshot(Equipo $equipo, UsuarioAsignado $ua): void
    {
        $equipo->update([
            'responsable_cedula' => $ua->cedula ?: null,
            'responsable_nombre' => $ua->nombre ?: null,
            'responsable_cargo'  => $ua->cargo ?: null,
            'responsable_ciudad' => $ua->ciudad ?: null,
            'responsable_area'   => $ua->area ?: null,
        ]);
    }

    /**
     * Resuelve (o crea) el funcionario del maestro a partir de la cédula del
     * snapshot, para enlazar `usuario_asignados.funcionario_id`.
     */
    public function resolverFuncionario(array $snapshot): ?Funcionario
    {
        $cedula = trim((string) ($snapshot['usuario_cedula'] ?? ''));
        if ($cedula === '') {
            return null;
        }

        $hash = hash_hmac('sha256', $cedula, config('app.key'));

        return Funcionario::withTrashed()
            ->where('identificacion_hash', $hash)
            ->first();
    }
}
