<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Services\Sri\RideGenerator;
use DomainException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DescargarRideController extends Controller
{
    public function __invoke(Factura $factura, RideGenerator $ride): Response
    {
        Gate::authorize('descargarRide', $factura);

        try {
            return $ride->generar($factura);
        } catch (DomainException $e) {
            abort(422, $e->getMessage());
        }
    }
}
