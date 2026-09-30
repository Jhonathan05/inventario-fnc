@extends('layouts.inventario')

@section('title', 'Detalle Checklist')

@section('content')
<x-ui.toolbar 
    title="Detalle de Checklist" 
    icon="clipboard-check"
    iconColor="info"
    backRoute="{{ route('checklists.index') }}"
>
    <x-ui.button href="{{ route('checklists.edit', $checklist) }}" color="warning" icon="pencil" text="Editar" class="text-white" />
</x-ui.toolbar>

<x-ui.card class="config-card-medium">
    <dl class="row mb-0">
        <dt class="col-sm-4 text-muted">Equipo</dt>
        <dd class="col-sm-8">
            @if($checklist->equipo)
                <a href="{{ route('equipos.show', $checklist->equipo) }}">
                    {{ $checklist->equipo->nombre_equipo }}
                </a>
                <span class="text-muted ms-2">({{ $checklist->equipo->serial_visual }})</span>
            @else
                —
            @endif
        </dd>
        <dt class="col-sm-4 text-muted">Responsable TI</dt>
        <dd class="col-sm-8">{{ $checklist->responsable_ti ?? '—' }}</dd>
        <dt class="col-sm-4 text-muted">Orden de Trabajo</dt>
        <dd class="col-sm-8">{{ $checklist->orden_trabajo ?? '—' }}</dd>
        <dt class="col-sm-4 text-muted">Cruce AV</dt>
        <dd class="col-sm-8">{{ $checklist->cruce_av ?? '—' }}</dd>
        <dt class="col-sm-4 text-muted">Cruce Short name</dt>
        <dd class="col-sm-8">{{ $checklist->crece_software ?? '—' }}</dd>
        <dt class="col-sm-4 text-muted">Resultado Cruce Antivirus</dt>
        <dd class="col-sm-8">{{ $checklist->resultado ?? '—' }}</dd>
        <dt class="col-sm-4 text-muted">Tipo Aprobado</dt>
        <dd class="col-sm-8">{{ $checklist->tipo_aprobado ?? '—' }}</dd>
        <dt class="col-sm-4 text-muted">FNC</dt>
        <dd class="col-sm-8">{{ $checklist->fnc ?? '—' }}</dd>
        <dt class="col-sm-4 text-muted">Observaciones</dt>
        <dd class="col-sm-8">{{ $checklist->observaciones ?? '—' }}</dd>
        <dt class="col-sm-4 text-muted">Registrado el</dt>
        <dd class="col-sm-8">{{ $checklist->created_at->format('d/m/Y H:i') }}</dd>
    </dl>
</x-ui.card>
@endsection
