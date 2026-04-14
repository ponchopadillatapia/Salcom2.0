<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'folio', 'codigo_cliente', 'nombre_cliente', 'productos',
        'total', 'tipo_pago', 'estatus', 'notas',
    ];

    protected $casts = [
        'productos' => 'array',
        'total' => 'decimal:2',
    ];
}
