<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraGasolina extends Model
{
    // POR QUÉ: Laravel pluraliza mal "gasolina"; fijamos el nombre real de la tabla a mano.
    protected $table = 'bitacoras_gasolina';

    protected $fillable = [
        'fecha', 'numero_empleado', 'empleado', 'cantidad_litros', 'rendimiento',
        'monto', 'vehiculo', 'kilometraje', 'notas', 'factura', 'origen',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad_litros' => 'decimal:2',
        'rendimiento' => 'decimal:2',
        'monto' => 'decimal:2',
        'kilometraje' => 'decimal:2',
    ];
}
