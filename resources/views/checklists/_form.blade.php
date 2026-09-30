{{-- Partial compartido: campos de checklist --}}

<div class="card mb-4">
    <div class="card-header bg-primary bg-opacity-10 border-0 fw-semibold py-3">
        <i class="bi bi-clipboard-check me-2 text-primary"></i>Datos del Checklist
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-medium">Equipo <span class="text-danger">*</span></label>
                <select name="equipo_id"
                        class="form-select @error('equipo_id') is-invalid @enderror"
                        required>
                    <option value="">Seleccione un equipo...</option>
                    @foreach($equipos as $eq)
                        <option value="{{ $eq->id }}"
                            {{ old('equipo_id', $checklist->equipo_id ?? request('equipo_id')) == $eq->id ? 'selected' : '' }}>
                            {{ $eq->nombre_equipo }} — {{ $eq->serial_visual }}
                        </option>
                    @endforeach
                </select>
                @error('equipo_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label fw-medium">Responsable TI</label>
                <div class="form-control bg-light">Se completara automaticamente desde el Responsable del Activo.</div>
                <small class="text-muted">La fecha del checklist tambien se registra automaticamente con la fecha actual del sistema.</small>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-medium">Orden de Remisión</label>
                <input type="text" name="orden_trabajo"
                       class="form-control @error('orden_trabajo') is-invalid @enderror"
                       value="{{ old('orden_trabajo', $checklist->orden_trabajo ?? '') }}"
                       maxlength="100">
                @error('orden_trabajo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-medium">Cruce AV</label>
                <input type="text" name="cruce_av"
                       class="form-control @error('cruce_av') is-invalid @enderror"
                       value="{{ old('cruce_av', $checklist->cruce_av ?? '') }}"
                       maxlength="100">
                @error('cruce_av') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-medium">Cruce Short name</label>
                <input type="text" name="crece_software"
                       class="form-control @error('crece_software') is-invalid @enderror"
                       value="{{ old('crece_software', $checklist->crece_software ?? '') }}"
                       maxlength="100">
                @error('crece_software') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-medium">Resultado Cruce Antivirus</label>
                <input type="text" name="resultado"
                       class="form-control @error('resultado') is-invalid @enderror"
                       value="{{ old('resultado', $checklist->resultado ?? '') }}"
                       maxlength="100">
                @error('resultado') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-medium">Tipo Aprobado</label>
                <input type="text" name="tipo_aprobado"
                       class="form-control @error('tipo_aprobado') is-invalid @enderror"
                       value="{{ old('tipo_aprobado', $checklist->tipo_aprobado ?? '') }}"
                       maxlength="100">
                @error('tipo_aprobado') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-medium">FNC</label>
                <input type="text" name="fnc"
                       class="form-control @error('fnc') is-invalid @enderror"
                       value="{{ old('fnc', $checklist->fnc ?? '') }}"
                       maxlength="100">
                @error('fnc') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-medium">Observaciones</label>
                <textarea name="observaciones"
                          class="form-control @error('observaciones') is-invalid @enderror"
                          rows="3"
                          maxlength="1000">{{ old('observaciones', $checklist->observaciones ?? '') }}</textarea>
                @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>
