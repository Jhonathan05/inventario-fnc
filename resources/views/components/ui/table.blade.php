<div class="table-responsive bg-white rounded shadow-sm w-100">
    <table {{ $attributes->merge(['class' => 'table table-hover align-middle mb-0']) }}>
        @if(isset($head))
            <thead class="bg-light text-nowrap">
                {{ $head }}
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
