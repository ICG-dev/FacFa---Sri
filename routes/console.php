<?php

use App\Jobs\ConsultarEstadoAnulacionJob;
use App\Models\Factura;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('reglas-facturacion:procesar')
    ->dailyAt('06:00')->timezone('America/Guayaquil')->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Factura::query()
        ->where('estado', Factura::ESTADO_PENDIENTE_ANULAR)
        ->each(fn (Factura $factura) => ConsultarEstadoAnulacionJob::dispatch($factura)->onQueue('sri'));
})->everyMinute()->when(
    fn () => now()->minute % max(1, (int) config('sri.consulta_anulacion_intervalo_minutos')) === 0
);
