<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read ProveedorUser|null $proveedor
 */
class Pedido extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'folio', 'codigo_cliente', 'nombre_cliente',
        'codigo_proveedor', 'nombre_proveedor', 'productos',
        'total', 'tipo_pago', 'estatus', 'notas',
    ];

    protected $casts = [
        'productos' => 'array',
        'total' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(ClienteUser::class, 'codigo_cliente', 'codigo_cliente');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorUser::class, 'codigo_proveedor', 'id_proveedor');
    }

    public function tracking()
    {
        return $this->hasMany(TrackingPedido::class, 'pedido_id');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'pedido_id');
    }
}
