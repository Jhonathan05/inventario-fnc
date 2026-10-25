{{-- Partial compartido: campos de checklist --}}

<div class="card mb-4">
    <div class="card-header bg-primary bg-opacity-10 border-0 fw-semibold py-3">
        <i class="bi bi-clipboard-check me-2 text-primary"></i>Datos del Checklist
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <x-ui.select name="equipo_id" label="Equipo *" required="true" containerClass="">
                    <option value="">Seleccione un equipo...</option>
                    @foreach($equipos as $eq)
                        <option value="{{ $eq->id }}"
                            {{ old('equipo_id', $checklist->equipo_id ?? request('equipo_id')) == $eq->id ? 'selected' : '' }}>
                            {{ $eq->nombre_equipo }} — {{ $eq->serial_visual }}
                        </option>
                    @endforeach
                </x-ui.select>
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label fw-medium">Responsable TI</label>
                <div class="form-control bg-light">Se completara automaticamente desde el Responsable del Activo.</div>
                <small class="text-muted">La fecha del checklist tambien se registra automaticamente con la fecha actual del sistema.</small>
            </div>

            <div class="col-12 col-lg-4">
                <x-ui.input name="orden_trabajo" label="Orden de Remisión" type="text" value="{{ old('orden_trabajo', $checklist->orden_trabajo ?? '') }}" containerClass="" maxlength="100" />
            </div>

            <div class="col-12 col-lg-4">
                <x-ui.input name="cruce_av" label="Cruce AV" type="text" value="{{ old('cruce_av', $checklist->cruce_av ?? '') }}" containerClass="" maxlength="100" />
            </div>

            <div class="col-12 col-lg-4">
                <x-ui.input name="crece_software" label="Cruce Short name" type="text" value="{{ old('crece_software', $checklist->crece_software ?? '') }}" containerClass="" maxlength="100" />
            </div>

            <div class="col-12 col-lg-4">
                <x-ui.input name="resultado" label="Resultado Cruce Antivirus" type="text" value="{{ old('resultado', $checklist->resultado ?? '') }}" containerClass="" maxlength="100" />
            </div>

            <div class="col-12 col-lg-4">
                <x-ui.input name="tipo_aprobado" label="Tipo Aprobado" type="text" value="{{ old('tipo_aprobado', $checklist->tipo_aprobado ?? '') }}" containerClass="" maxlength="100" />
            </div>

            <div class="col-12 col-lg-4">
                <x-ui.input name="fnc" label="FNC" type="text" value="{{ old('fnc', $checklist->fnc ?? '') }}" containerClass="" maxlength="100" />
            </div>

            <div class="col-12">
                <x-ui.textarea name="observaciones" label="Observaciones" rows="3" containerClass="" maxlength="1000">{{ old('observaciones', $checklist->observaciones ?? '') }}</x-ui.textarea>
            </div>
        </div>
    </div>
</div>
