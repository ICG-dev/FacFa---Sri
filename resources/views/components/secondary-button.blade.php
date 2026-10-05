<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 h-9 px-4 py-2 bg-secondary text-secondary-foreground rounded-md text-sm font-semibold hover:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:opacity-50 disabled:pointer-events-none transition-opacity']) }}>
    {{ $slot }}
</button>
