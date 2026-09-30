@extends('layouts.inventario')

@section('title', 'Plantillas PDF')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Plantillas de Documentos PDF
    </h4>
    <a href="{{ route('plantillas-pdf.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nueva Plantilla
    </a>
</div>

<div class="row g-4">
    @forelse($plantillas as $plantilla)
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100 {{ $plantilla->activa ? 'border-success border-start border-4' : '' }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="fw-bold mb-1">
                            {{ $plantilla->nombre }}
                            @if($plantilla->activa)
                                <span class="badge bg-success ms-2">Activa</span>
                            @else
                                <span class="badge bg-secondary ms-2">Inactiva</span>
                            @endif
                        </h6>
                        <small class="text-muted">
                            Tipo: <strong>{{ \App\Models\PlantillaPdf::TIPOS[$plantilla->tipo] ?? $plantilla->tipo }}</strong>
                        </small>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" 
                                data-bs-toggle="dropdown" data-bs-boundary="window">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('plantillas-pdf.show', $plantilla) }}">
                                    <i class="bi bi-eye me-2"></i>Ver
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('plantillas-pdf.edit', $plantilla) }}">
                                    <i class="bi bi-pencil me-2"></i>Editar
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button type="button"
                                        class="dropdown-item text-danger"
                                        data-delete-url="{{ route('plantillas-pdf.destroy', $plantilla) }}"
                                        data-delete-name="{{ $plantilla->nombre }}">
                                    <i class="bi bi-trash me-2"></i>Eliminar
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <p class="small text-muted mb-2">
                    Creada por: {{ $plantilla->creadaPor?->name ?? '—' }}
                    · {{ $plantilla->created_at?->format('d/m/Y') }}
                </p>

                <div class="p-3 bg-light config-preview-box">
                    <small class="text-muted d-block text-truncate">{{ Str::limit(strip_tags($plantilla->contenido), 150) }}</small>
                    <div class="config-preview-fade"></div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <a href="{{ route('plantillas-pdf.edit', $plantilla) }}"
                       class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                    <a href="{{ route('plantillas-pdf.show', $plantilla) }}"
                       class="btn btn-sm btn-outline-info">
                        <i class="bi bi-eye me-1"></i>Ver variables
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-file-earmark-pdf fs-2 d-block mb-2"></i>
                No hay plantillas creadas.
                <br>
                <a href="{{ route('plantillas-pdf.create') }}" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-lg me-1"></i>Nueva plantilla
                </a>
            </div>
        </div>
    </div>
    @endforelse
</div>
@endsection

