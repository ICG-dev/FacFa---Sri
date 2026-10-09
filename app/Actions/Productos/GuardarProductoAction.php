<?php

namespace App\Actions\Productos;

use App\Models\Producto;
use App\Models\TarifaIva;

class GuardarProductoAction
{
    public function ejecutar(?Producto $producto, int $empresaId, array $datos): Producto
    {
        $productoModel = $producto ?? new Producto([
            'empresa_id' => $empresaId,
        ]);

        $tarifa = TarifaIva::paraCodigo((string) $datos['codigo_porcentaje']);
        $productoModel->fill([...$datos, 'tarifa' => $tarifa->porcentaje]);
        $productoModel->save();

        return $productoModel;
    }
}
