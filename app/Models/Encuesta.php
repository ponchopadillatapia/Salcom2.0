<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Encuesta extends Model
{
    protected $fillable = [
        'codigo_cliente', 'pedido_id', 'calificacion',
        'tiempo_entrega', 'calidad_producto', 'comentarios',
    ];
}
