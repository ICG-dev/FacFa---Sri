<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 h-9 px-4 py-2 bg-accent text-accent-foreground rounded-md text-sm font-semibold shadow-sm hover:bg-accent/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:opacity-50 disabled:pointer-events-none transition-colors']) }}>
    {{ $slot }}
</button>
