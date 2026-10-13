<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reembolso extends Model
{
    protected $table = 'reembolsos';

    protected $fillable = [
        'numero_empleado', 'solicitante', 'numero_cuenta', 'titular_cuenta',
        'categoria', 'razon_social', 'metodo_pago_empresa',
        'uso_cfdi', 'forma_pago', 'metodo_pago',
        'monto', 'concepto', 'fecha_factura',
        'archivo_factura', 'archivo_xml', 'archivo_materialidad',
        'notas', 'estatus', 'autorizado', 'autorizado_fecha', 'origen',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_factura' => 'date',
        'autorizado' => 'boolean',
        'autorizado_fecha' => 'datetime',
    ];
}
