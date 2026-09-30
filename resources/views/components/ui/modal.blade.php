@props([
    'id',
    'title',
    'formId' => null,
    'action' => null,
    'method' => 'POST',
    'submitText' => 'Guardar',
    'cancelText' => 'Cancelar',
    'size' => '', // modal-lg, modal-sm
    'submitIcon' => 'save',
    'submitId' => null
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog {{ $size }} modal-dialog-centered modal-dialog-scrollable">
        @if($formId || $action)
            <form id="{{ $formId ?? $id . 'Form' }}" class="modal-content border-0 shadow-lg" {{ $action ? 'action='.$action : '' }} method="{{ $method === 'GET' ? 'GET' : 'POST' }}">
                @if(in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE']))
                    @method(strtoupper($method))
                @endif
                @if(strtoupper($method) !== 'GET')
                    @csrf
                @endif
        @else
            <div class="modal-content border-0 shadow-lg">
        @endif

            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="{{ $id }}Label">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                {{ $slot }}
            </div>
            
            <div class="modal-footer border-top-0 bg-light rounded-bottom d-flex justify-content-end gap-2">
                @if(isset($footer))
                    {{ $footer }}
                @else
                    <x-ui.button type="button" color="secondary" outline="true" data-bs-dismiss="modal" text="{{ $cancelText }}" />
                    @if($formId || $action)
                        <x-ui.button type="submit" color="primary" icon="{{ $submitIcon }}" text="{{ $submitText }}" id="{{ $submitId }}" />
                    @endif
                @endif
            </div>

        @if($formId || $action)
            </form>
        @else
            </div>
        @endif
    </div>
</div>
