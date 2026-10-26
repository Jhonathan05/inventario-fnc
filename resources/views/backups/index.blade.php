@extends('layouts.inventario')

@section('title', 'Backups')

@section('content')
<div class="container-fluid py-4">
    <x-ui.toolbar
        title="Backups"
        icon="database"
        subtitle="Volcados comprimidos de la base de datos del inventario, guardados en disco privado"
    />

    {{-- Resumen del módulo --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.08em;">Backups guardados</div>
                    <div class="fs-4 fw-bold">{{ $backups->count() }}</div>
                    <div class="text-muted small">de {{ $retencion }} que conserva la retención</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.08em;">Programación</div>
                    <div class="fs-6 fw-bold mt-1">
                        @if ($programado)
                            <x-fnc.pill tone="success" dot>Diaria a las 02:00</x-fnc.pill>
                        @else
                            <x-fnc.pill tone="slate" dot>Solo manual</x-fnc.pill>
                        @endif
                    </div>
                    <div class="text-muted small mt-1">
                        @if ($programado)
                            El job <code>backups:crear</code> corre en el scheduler.
                        @else
                            Actívala con <code>BACKUPS_PROGRAMADO=true</code>.
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.08em;">Formato</div>
                    <div class="fs-6 fw-bold mt-1"><x-fnc.pill tone="info">MySQL · gzip</x-fnc.pill></div>
                    <div class="text-muted small mt-1"><code>{{ $prefijo }}_backup_<fecha>.sql.gz</code></div>
                </div>
            </div>
        </div>
    </div>

    <x-ui.card noPadding="true" title="Backups disponibles" icon="database">
        <x-slot:headerActions>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#modalRestaurarBackup" title="Sube un volcado .sql.gz y lo restaura sobre la base actual">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar respaldo…
            </button>
            <form method="POST" action="{{ route('backups.crear') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm" title="Genera un volcado gzip de la base de datos actual">
                    <i class="bi bi-plus-lg me-1"></i> Crear backup ahora
                </button>
            </form>
        </x-slot:headerActions>

        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <th class="ps-4">Backup</th>
                    <th>Tamaño</th>
                    <th>Creado</th>
                    <th class="text-end pe-4">Acciones</th>
                </tr>
            </x-slot>

            @forelse ($backups as $backup)
                @php $cuando = \Carbon\Carbon::createFromTimestamp($backup['fecha']); @endphp
                <tr>
                    <td class="ps-4">
                        <div class="fnc-udash__asset">
                            <span class="fnc-udash__ic" aria-hidden="true">
                                <x-fnc.icon name="database" :size="16" />
                            </span>
                            <span class="fnc-udash__names">
                                <span class="fnc-udash__name">{{ $backup['nombre'] }}</span>
                                <span class="fnc-udash__sub">Volcado MySQL comprimido (gzip)</span>
                            </span>
                        </div>
                    </td>
                    <td class="text-nowrap">
                        <x-fnc.pill tone="info">
                            {{ $backup['bytes'] >= 1048576
                                ? number_format($backup['bytes'] / 1048576, 1) . ' MB'
                                : number_format($backup['bytes'] / 1024, 1) . ' KB' }}
                        </x-fnc.pill>
                    </td>
                    <td class="text-nowrap">
                        <span class="fnc-udash__when">
                            <span class="fnc-udash__whenrel">{{ $cuando->diffForHumans() }}</span>
                            <span class="fnc-udash__sub">{{ $cuando->format('d/m/Y H:i') }}</span>
                        </span>
                    </td>
                    <td class="text-end pe-4 text-nowrap">
                        <span class="d-inline-flex gap-1 justify-content-end">
                            <a href="{{ route('backups.descargar', ['archivo' => $backup['nombre'], '_tab' => request('_tab')]) }}"
                               class="fnc-udash__ver" title="Descargar" aria-label="Descargar {{ $backup['nombre'] }}">
                                <x-fnc.icon name="download" :size="15" />
                            </a>
                            <button type="button" class="fnc-udash__ver" data-restaurar-backup="{{ $backup['nombre'] }}"
                                    title="Restaurar este respaldo sobre la base actual"
                                    aria-label="Restaurar {{ $backup['nombre'] }}">
                                <x-fnc.icon name="refresh-cw" :size="15" />
                            </button>
                            <form method="POST" action="{{ route('backups.eliminar', ['archivo' => $backup['nombre'], '_tab' => request('_tab')]) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Eliminar el backup {{ $backup['nombre'] }}? Esta acción no se puede deshacer.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="fnc-udash__ver text-danger" title="Eliminar" aria-label="Eliminar {{ $backup['nombre'] }}">
                                    <x-fnc.icon name="trash" :size="15" />
                                </button>
                            </form>
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-5 text-muted">
                        <i class="bi bi-database fs-2 d-block mb-2 opacity-50"></i>
                        No hay backups todavía. Crea el primero con «Crear backup ahora».
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>

    {{-- ── Modal: restaurar un respaldo subido ───────────────────────────── --}}
    <x-ui.modal id="modalRestaurarBackup" title="Restaurar respaldo" size="modal-lg" cancelText="Cerrar">
        <form id="formRestaurarBackup"
              action="{{ route('backups.restaurar', ['_tab' => request('_tab')]) }}"
              method="POST"
              enctype="multipart/form-data">
            @csrf

            <div class="alert alert-warning d-flex gap-2 align-items-start mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <div>
                    <strong>Restaurar reemplaza TODA la base de datos actual</strong>
                    (equipos, funcionarios, actas, correcciones, usuarios…) por el contenido del
                    archivo. Antes de tocar nada, el aplicativo crea un
                    <strong>backup de retorno automático</strong> que queda en esta lista.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold" for="archivoRestaurarBackup">Archivo del volcado</label>
                <input type="file" class="form-control" id="archivoRestaurarBackup" name="archivo"
                       accept=".gz,.gzip,.sql" required>
                <div class="form-text">
                    Formato: <code>.sql.gz</code> generado por este módulo (también acepta el
                    <code>.sql</code> sin comprimir). Máximo
                    {{ (int) config('backups.max_subida_kb', 65536) >= 1024
                        ? number_format(config('backups.max_subida_kb', 65536) / 1024, 0) . ' MB'
                        : config('backups.max_subida_kb', 65536) . ' KB' }}.
                </div>
                <div class="form-text text-warning-emphasis">
                    Si el archivo viene de <em>otra</em> instancia, esa instancia debe usar la misma
                    <code>APP_KEY</code>; si no, el aplicativo detecta los datos cifrados ilegibles y
                    devuelve la base a su estado anterior.
                </div>
                <div id="restaurarBackupInfo" class="form-text fw-semibold d-none"></div>
            </div>
        </form>

        <x-slot name="footer">
            <x-ui.button type="button" color="secondary" outline="true" data-bs-dismiss="modal" text="Cerrar" />
            <x-ui.button type="submit" form="formRestaurarBackup" color="danger" icon="arrow-counterclockwise"
                         text="Restaurar" id="btnRestaurarBackup" />
        </x-slot>
    </x-ui.modal>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const limiteKb = {{ (int) config('backups.max_subida_kb', 65536) }};
        const rutaRestaurarExistente = (archivo) =>
            '{{ route('backups.restaurar.existente', ['archivo' => 'NOMBRE', '_tab' => request('_tab')]) }}'.replace('NOMBRE', encodeURIComponent(archivo));

        function escapar(texto) {
            const contenedor = document.createElement('div');
            contenedor.textContent = String(texto);
            return contenedor.innerHTML;
        }

        function avisando(titulo) {
            Swal.fire({
                title: titulo,
                html: 'Se crea primero un backup de retorno y luego se importa el volcado.<br>Unos segundos…',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });
        }

        function restaurar(url, datos) {
            return fetch(url, {
                method: 'POST',
                body: datos,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then((respuesta) => respuesta.json().then((cuerpo) => ({ ok: respuesta.ok, cuerpo })))
                .then(({ ok, cuerpo }) => {
                    Swal.close();

                    if (ok && cuerpo.success) {
                        return Swal.fire({
                            icon: 'success',
                            title: 'Respaldo restaurado',
                            html: escapar(cuerpo.success),
                            confirmButtonText: 'Ver módulo'
                        }).then(() => window.location.reload());
                    }

                    return Swal.fire({
                        icon: 'error',
                        title: 'No se pudo restaurar',
                        html: escapar(cuerpo.error || cuerpo.message || 'Error desconocido.')
                    });
                })
                .catch(() => Swal.fire({
                    icon: 'error',
                    title: 'Error de red',
                    text: 'La petición no llegó al servidor. Revisa el tamaño del archivo.'
                }));
        }

        // ── Restaurar un respaldo subido (modal) ──────────────────────────
        const form = document.getElementById('formRestaurarBackup');
        const input = document.getElementById('archivoRestaurarBackup');
        const info = document.getElementById('restaurarBackupInfo');

        if (form && input) {
            input.addEventListener('change', function () {
                const archivo = this.files[0];
                info.classList.add('d-none');

                if (!archivo) { return; }

                const kb = Math.round(archivo.size / 1024);
                info.innerHTML = '<i class="bi bi-file-earmark-zip me-1"></i>'
                    + escapar(archivo.name) + ' · '
                    + (kb >= 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB')
                    + (kb > limiteKb ? ' — <span class="text-danger fw-bold">supera el límite de '
                        + Math.round(limiteKb / 1024) + ' MB</span>' : '');
                info.classList.remove('d-none');
            });

            form.addEventListener('submit', function (evento) {
                evento.preventDefault();

                const archivo = input.files[0];

                if (!archivo) {
                    Swal.fire({ icon: 'warning', title: 'Selecciona el archivo .sql.gz a restaurar' });
                    return;
                }

                if (Math.round(archivo.size / 1024) > limiteKb) {
                    Swal.fire({ icon: 'error', title: 'Archivo demasiado grande',
                        text: 'Supera el máximo de ' + Math.round(limiteKb / 1024) + ' MB.' });
                    return;
                }

                Swal.fire({
                    icon: 'warning',
                    title: '¿Restaurar este respaldo?',
                    html: 'Se <strong>reemplazarán todos los datos actuales</strong> por los de<br><code>'
                        + escapar(archivo.name) + '</code>.<br>Antes se crea un backup de retorno automático.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, restaurar',
                    confirmButtonColor: '#dc3545',
                    cancelButtonText: 'Cancelar'
                }).then((r) => {
                    if (!r.isConfirmed) { return; }

                    avisando('Restaurando…');

                    const datos = new FormData(form);
                    datos.set('archivo', archivo);

                    restaurar(form.action, datos);
                });
            });
        }

        // ── Restaurar un respaldo ya guardado (botón por fila) ─────────────
        document.querySelectorAll('[data-restaurar-backup]').forEach((boton) => {
            boton.addEventListener('click', function () {
                const nombre = this.getAttribute('data-restaurar-backup');

                Swal.fire({
                    icon: 'warning',
                    title: '¿Restaurar este respaldo?',
                    html: 'Se <strong>reemplazarán todos los datos actuales</strong> por los de<br><code>'
                        + escapar(nombre) + '</code>.<br>Antes se crea un backup de retorno automático.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, restaurar',
                    confirmButtonColor: '#dc3545',
                    cancelButtonText: 'Cancelar'
                }).then((r) => {
                    if (!r.isConfirmed) { return; }

                    avisando('Restaurando…');

                    const datos = new FormData();
                    datos.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                    restaurar(rutaRestaurarExistente(nombre), datos);
                });
            });
        });
    });
</script>
@endpush
@endsection
