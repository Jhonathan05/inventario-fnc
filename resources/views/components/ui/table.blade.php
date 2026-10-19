<div class="table-responsive w-100">
    <table {{ $attributes->merge(['class' => 'table table-hover align-middle mb-0']) }}>
        @if(isset($head))
            <thead class="text-nowrap">
                {{ $head }}
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
