<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Factura extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'folio_cfdi', 'codigo_cliente', 'codigo_proveedor', 'pedido_id',
        'monto', 'monto_iva', 'total', 'estatus', 'fecha_vencimiento',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'monto_iva' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function cliente()
    {
        return $this->belongsTo(ClienteUser::class, 'codigo_cliente', 'codigo_cliente');
    }

    public function proveedor()
    {
        return $this->belongsTo(ProveedorUser::class, 'codigo_proveedor', 'codigo_compras');
    }
}
