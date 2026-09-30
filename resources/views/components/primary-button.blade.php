<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#9e052b] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#7f0422] focus:bg-[#7f0422] active:bg-[#65031b] focus:outline-none focus:ring-2 focus:ring-[#9e052b] focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
