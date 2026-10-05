<?php

namespace App\Actions\Productos;

use App\Models\Producto;

class GuardarProductoAction
{
    


    public function ejecutar(?Producto $producto, int $empresaId, array $datos): Producto
    {
        $productoModel = $producto ?? new Producto([
            'empresa_id' => $empresaId,
        ]);

        $productoModel->fill($datos);
        $productoModel->save();

        return $productoModel;
    }
}
