<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — {{ __('Página no encontrada') }} | {{ config('app.name') }}</title>
    @include('partials.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-background text-foreground min-h-screen flex items-center justify-center p-4">
    <main class="max-w-md w-full text-center space-y-6 bg-card border border-border p-8 rounded-xl shadow-lg">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-secondary text-primary font-heading font-extrabold text-2xl">
            404
        </div>
        <div class="space-y-2">
            <h1 class="text-xl font-heading font-bold text-foreground">{{ __('Página no encontrada') }}</h1>
            <p class="text-sm text-muted-foreground">
                {{ __('La página que buscas no existe, fue eliminada o su identificador no es válido.') }}
            </p>
        </div>
        <div class="pt-2">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-accent text-accent-foreground font-semibold text-sm hover:opacity-90 transition-opacity focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background">
                {{ __('Volver al Dashboard') }}
            </a>
        </div>
    </main>
</body>
</html>
