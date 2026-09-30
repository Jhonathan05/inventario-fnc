@extends('layouts.inventario')

@section('title', 'Ver Plantilla PDF')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>{{ $plantillasPdf->nombre }}
    </h4>
    <div class="d-flex gap-2">
        <a href="{{ route('plantillas-pdf.edit', $plantillasPdf) }}" class="btn btn-warning text-white">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <a href="{{ route('plantillas-pdf.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <strong>Contenido de la Plantilla</strong>
                @if($plantillasPdf->activa)
                    <span class="badge bg-success ms-2">Activa</span>
                @endif
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded small config-pre-wrap config-scrollable-500">{{ $plantillasPdf->contenido }}</pre>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info bg-opacity-10 fw-semibold border-0 py-3">
                <i class="bi bi-braces me-2 text-info"></i>Variables Reconocidas
            </div>
            <div class="card-body p-0">
                <div class="config-scrollable-400">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach($variables as $var => $desc)
                            @if(str_contains($plantillasPdf->contenido, $var))
                            <tr class="table-success">
                                <td><code class="small text-success">{{ $var }}</code></td>
                                <td class="small">{{ $desc }}</td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

