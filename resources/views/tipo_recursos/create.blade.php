@extends('layouts.inventario')

@push('styles')
<style>
    /* ── Tarjetas de complemento ─────────────────────────────────────────── */
    .comp-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        background: #fff;
        user-select: none;
    }
    .comp-card:hover {
        border-color: #6366f1;
        background-color: #f5f3ff;
        box-shadow: 0 2px 8px rgba(99,102,241,0.10);
    }
    .comp-card input[type="checkbox"] {
        accent-color: #6366f1;
        width: 17px;
        height: 17px;
        flex-shrink: 0;
        cursor: pointer;
    }
    .comp-card.selected {
        border-color: #6366f1;
        background-color: #ede9fe;
        box-shadow: 0 2px 10px rgba(99,102,241,0.15);
    }
    .comp-card .comp-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.85rem;
        flex-shrink: 0;
    }
    .comp-card.selected .comp-icon {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
    }
    .comp-card .comp-name {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        line-height: 1.3;
    }
    .comp-card.selected .comp-name { color: #4338ca; }

    /* ── Nombre input decorado ──────────────────────────────────────────── */
    .nombre-input-wrapper .form-control {
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        padding: 10px 14px;
        font-size: 0.95rem;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .nombre-input-wrapper .form-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
    }

    /* ── Sección de complementos ─────────────────────────────────────────── */
    .complementos-section {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 18px;
    }
    .complementos-section .section-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
    }
    .complementos-section .section-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.8rem;
    }
    .selected-counter {
        font-size: 0.78rem;
        background: #ede9fe;
        color: #5b21b6;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: auto;
    }
</style>
@endpush

@section('title', 'Nuevo Tipo de Recurso')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <x-ui.toolbar 
            title="Nuevo Tipo de Recurso" 
            icon="plus-circle" 
            backRoute="{{ route('tipo-recursos.index') }}"
        />

        <x-ui.card class="border-0 shadow-sm p-2">
            <form method="POST" action="{{ route('tipo-recursos.store') }}" novalidate>
                @csrf

                {{-- Nombre del tipo de recurso --}}
                <div class="mb-4 nombre-input-wrapper">
                    <label class="form-label fw-semibold" for="nombre">
                        Nombre del Tipo de Recurso
                        <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border:1.5px solid #e2e8f0; border-right:0; border-radius:10px 0 0 10px;">
                            <i class="bi bi-tag text-primary"></i>
                        </span>
                        <input 
                            type="text" 
                            name="nombre" 
                            id="nombre"
                            class="form-control border-start-0 @error('nombre') is-invalid @enderror"
                            placeholder="Ej: Laptop, PC, Tablet, Impresora..."
                            value="{{ old('nombre') }}"
                            maxlength="100"
                            oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '')"
                            required
                            style="border:1.5px solid #e2e8f0; border-left:0; border-radius:0 10px 10px 0;"
                        >
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <small class="text-muted mt-1 d-block">
                        <i class="bi bi-info-circle me-1"></i>
                        Define la categoría del activo tecnológico en el inventario.
                    </small>
                </div>

                {{-- Complementos --}}
                <div class="mb-4">
                    <div class="complementos-section">
                        <div class="section-header d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                <div class="section-icon">
                                    <i class="bi bi-puzzle"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark" style="font-size:0.9rem;">Complementos Definidos</div>
                                    <div class="text-muted" style="font-size:0.78rem;">Accesorios incluidos al registrar este tipo de activo</div>
                                </div>
                            </div>
                            <span class="selected-counter ms-0 ms-sm-auto mt-1 mt-sm-0 text-nowrap" id="contadorSeleccionados">0 seleccionados</span>
                        </div>

                        @if($catalogoComplementos->isEmpty())
                            <div class="d-flex align-items-center gap-2 text-muted p-3 bg-white rounded-3 border">
                                <i class="bi bi-exclamation-circle text-warning fs-5"></i>
                                <span class="small">No hay complementos en el catálogo. Agréguelos primero desde el módulo de Complementos.</span>
                            </div>
                        @else
                            <div class="row g-2" id="gridComplementos">
                                @foreach($catalogoComplementos as $comp)
                                    <div class="col-12 col-sm-6 col-md-4">
                                        <label class="comp-card w-100" for="comp{{ $comp->id }}">
                                            <input 
                                                class="comp-checkbox" 
                                                type="checkbox" 
                                                name="complementos_ids[]" 
                                                value="{{ $comp->id }}" 
                                                id="comp{{ $comp->id }}"
                                            >
                                            <div class="comp-icon">
                                                <i class="bi bi-box-seam"></i>
                                            </div>
                                            <span class="comp-name">{{ $comp->nombre }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Botones --}}
                <div class="d-flex gap-2 justify-content-end pt-3 border-top mt-2">
                    <x-ui.button 
                        href="{{ route('tipo-recursos.index') }}" 
                        color="light" 
                        class="border px-3 text-nowrap"
                        icon="x-lg"
                        text="Cancelar" 
                    />
                    <x-ui.button 
                        type="submit" 
                        color="primary" 
                        class="px-3 text-nowrap"
                        icon="floppy"
                        text="Guardar Tipo" 
                    />
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.comp-checkbox');
    const counter    = document.getElementById('contadorSeleccionados');

    function updateCounter() {
        const total = document.querySelectorAll('.comp-checkbox:checked').length;
        counter.textContent = total === 0 ? '0 seleccionados' : `${total} seleccionado${total > 1 ? 's' : ''}`;
    }

    checkboxes.forEach(function (cb) {
        const card = cb.closest('.comp-card');

        // Estado inicial
        if (cb.checked) card.classList.add('selected');

        cb.addEventListener('change', function () {
            if (this.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
            updateCounter();
        });
    });

    updateCounter();
});
</script>
@endpush
