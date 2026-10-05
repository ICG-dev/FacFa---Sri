@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'h-9 w-full rounded-md border border-border bg-input-background px-3 py-1 text-sm text-foreground shadow-sm placeholder:text-muted-foreground focus:outline-none focus:border-ring focus:ring-1 focus:ring-ring disabled:opacity-50']) }}>
