@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-[#9e052b] focus:ring-[#9e052b] rounded-md shadow-sm']) }}>
