<x-guest-layout>
    <div class="w-full max-w-[420px] mx-auto flex flex-col items-center">

        <!-- Logo y Encabezado -->
        <div class="text-center mb-8 w-full flex flex-col items-center">
            <img src="{{ asset('imagenes/federacion cafeteros logo.png') }}" alt="Logo Federación de Cafeteros" class="h-[80px] w-auto mb-4 object-contain">

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 w-full mb-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-amber-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-bold text-amber-800">Cambio de contraseña obligatorio</h3>
                        <p class="text-xs text-amber-700 mt-1">Por seguridad, debe cambiar su contraseña antes de acceder al sistema. Esta acción solo se requiere una vez.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Errores de validación -->
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 w-full mb-4">
                <ul class="text-xs text-red-700 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li class="flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.force-change.update') }}" class="w-full" id="force-password-change-form">
            @csrf
            @method('PUT')

            <!-- Nueva contraseña -->
            <div class="mb-5">
                <label for="password" class="block font-bold text-xs text-gray-700 mb-1.5">Nueva contraseña</label>
                <input id="password"
                       class="block w-full border-0 bg-[#f8f9fa] rounded-lg focus:ring-2 focus:ring-[#9e052b] focus:bg-white px-4 py-3.5 text-sm text-gray-900 placeholder-gray-400 transition-colors tracking-widest"
                       type="password"
                       name="password"
                       required
                       autofocus
                       autocomplete="new-password"
                       placeholder="••••••••" />
            </div>

            <!-- Confirmar contraseña -->
            <div class="mb-6">
                <label for="password_confirmation" class="block font-bold text-xs text-gray-700 mb-1.5">Confirmar contraseña</label>
                <input id="password_confirmation"
                       class="block w-full border-0 bg-[#f8f9fa] rounded-lg focus:ring-2 focus:ring-[#9e052b] focus:bg-white px-4 py-3.5 text-sm text-gray-900 placeholder-gray-400 transition-colors tracking-widest"
                       type="password"
                       name="password_confirmation"
                       required
                       autocomplete="new-password"
                       placeholder="••••••••" />
            </div>

            <!-- Botón -->
            <div>
                <button type="submit" class="w-full bg-[#9e052b] hover:bg-[#7a0421] text-white font-semibold py-3.5 px-4 rounded-lg transition duration-200 ease-in-out shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#9e052b] text-sm">
                    Cambiar contraseña y acceder
                </button>
            </div>
        </form>

        <div class="mt-4 text-center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
