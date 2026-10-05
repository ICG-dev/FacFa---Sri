<script>
    function aplicarTema() {
        const oscuro = localStorage.theme === 'dark'
            || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.classList.toggle('dark', oscuro);
    }

    
    
    
    
    aplicarTema();
    document.addEventListener('livewire:navigated', aplicarTema);
</script>
