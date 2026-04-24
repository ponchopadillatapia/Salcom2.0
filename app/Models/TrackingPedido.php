<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingPedido extends Model
{
    protected $table = 'tracking_pedidos';

    protected $fillable = [
        'pedido_id', 'estatus', 'descripcion',
        'fecha', 'usuario_responsable',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}
