{{-- Copie du gabarit de Laravel : le nom de l'instance (config/marque.php) remplace APP_NAME en marque blanche. --}}<x-mail::layout>
    {{-- Header --}}    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            {{ (\App\Support\MarqueInstance::personnalisee() ? config('marque.nom') : config('app.name')) }}
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}    {{ $slot }}

    {{-- Subcopy --}}    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} {{ (\App\Support\MarqueInstance::personnalisee() ? config('marque.nom') : config('app.name')) }}. @lang('All rights reserved.')
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
