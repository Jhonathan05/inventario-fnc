<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        ¡Gracias por registrarse! Antes de comenzar, verifique su correo electrónico haciendo clic en el enlace que le enviamos. Si no lo recibió, con gusto le enviaremos otro.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Se ha enviado un nuevo enlace de verificación al correo electrónico que proporcionó durante el registro.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <div>
                <x-primary-button>
                    Reenviar correo de verificación
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#9e052b]">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
