# Facturación electrónica SRI
Aplicación multiempresa con Laravel 12 y Livewire 3.
Requisitos: PHP 8.2+, Composer, Node.js, PostgreSQL y Redis.
Extensiones PHP: SOAP, OpenSSL, DOM, PDO PostgreSQL y SQLite para pruebas.
Instalar dependencias con `composer install` y `npm ci`.
Crear `.env` desde `.env.example` y configurar `APP_KEY` sin rotar claves existentes.
Configurar `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`.
Configurar Redis (`REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`) y `QUEUE_CONNECTION=redis`.
Usar `APP_TIMEZONE=America/Guayaquil`; en producción, `APP_DEBUG=false`.
Revisar y respaldar la base antes de aplicar migraciones con `php artisan migrate`.
Compilar recursos con `npm run build` y servir la carpeta `public/`.
Procesar colas con `php artisan horizon` o `php artisan queue:work --queue=sri,default`.
Mantener el programador activo con `php artisan schedule:work`.
El administrador carga el certificado `.p12` y su contraseña desde «Mi empresa»; se almacena en disco privado.
Ejecutar `php artisan test`: usa SQLite en memoria y clientes SRI simulados.
