<x-app-layout>
    <x-slot name="header">
        <h1 class="font-heading font-extrabold text-lg md:text-xl text-foreground tracking-tight">
            {{ __('Perfil') }}
        </h1>
    </x-slot>

    <div class="space-y-6 max-w-3xl">
        <div class="p-4 sm:p-8 bg-card text-card-foreground border border-border rounded-lg">
            <div class="max-w-xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-card text-card-foreground border border-border rounded-lg">
            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-card text-card-foreground border border-border rounded-lg">
            <div class="max-w-xl">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-app-layout>
