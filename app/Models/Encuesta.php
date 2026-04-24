<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Encuesta extends Model
{
    protected $fillable = [
        'codigo_cliente', 'pedido_id', 'calificacion',
        'tiempo_entrega', 'calidad_producto', 'comentarios',
    ];

    public function cliente()
    {
        return $this->belongsTo(ClienteUser::class, 'codigo_cliente', 'codigo_cliente');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}
