<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#16181d] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#000000] focus:bg-[#000000] active:bg-[#000000] focus:outline-none focus:ring-2 focus:ring-[#16181d] focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
