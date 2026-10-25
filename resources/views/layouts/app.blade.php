<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        @include('partials.theme-script')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-background text-foreground">
        <div
            class="flex h-screen w-full overflow-hidden"
            x-data="{ sidebarOpen: true, mobileOpen: false }"
            x-init="sidebarOpen = window.innerWidth >= 1024"
        >
            <x-app-sidebar />

            <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
                <livewire:layout.navigation />

                <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
                    @if (isset($header))
                        <div class="mb-4 md:mb-6">
                            {{ $header }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-alerta-modal />
    </body>
</html>
