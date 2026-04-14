<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'tipo_usuario', 'codigo_usuario', 'titulo',
        'mensaje', 'leida', 'tipo',
    ];

    protected $casts = [
        'leida' => 'boolean',
    ];
}
