@extends('layouts.inventario')

@section('title', 'Cambiar Contraseña')

@section('content')
<x-ui.toolbar 
    title="Cambiar contraseña - Solicitud #{{ $solicitud->id }}" 
    icon="key"
/>

<x-ui.card>
    <div class="mb-3">
        <div class="small text-muted">Usuario</div>
        <div class="fw-semibold">{{ $solicitud->usuario?->name ?? 'Usuario eliminado' }} ({{ $solicitud->email }})</div>
    </div>

    <form method="POST" action="{{ route('solicitudes-password.update-password', $solicitud) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="password" class="form-label fw-semibold">Nueva contraseña</label>
            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required minlength="8" autocomplete="new-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label fw-semibold">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
        </div>

        <div class="mb-4">
            <label for="observacion" class="form-label fw-semibold">Observación (opcional)</label>
            <textarea name="observacion" id="observacion" rows="3" class="form-control @error('observacion') is-invalid @enderror">{{ old('observacion') }}</textarea>
            @error('observacion')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex flex-wrap gap-2">
            <x-ui.button type="submit" color="primary" text="Guardar cambio" />
            <x-ui.button href="{{ route('solicitudes-password.show', $solicitud) }}" color="secondary" text="Cancelar" />
        </div>
    </form>
</x-ui.card>
@endsection
