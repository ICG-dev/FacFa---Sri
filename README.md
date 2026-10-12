# Facturación Electrónica SRI

Aplicación multiempresa desarrollada con Laravel 12 y Livewire 3 para la emisión, firma y autorización de comprobantes electrónicos ante el SRI (Ecuador).

---

## Requisitos

- PHP 8.2+
- Composer
- Node.js y npm
- PostgreSQL y SQLite
- Redis
- Extensiones PHP: soap, openssl, dom, pdo_pgsql, pdo_sqlite

---

## Instalación

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
