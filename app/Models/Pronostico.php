<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pronostico extends Model
{
    protected $fillable = [
        'tipo', 'referencia_tipo', 'referencia_id',
        'codigo_referencia', 'resultado', 'datos',
        'confianza', 'generado_at',
    ];

    protected $casts = [
        'datos' => 'array',
        'generado_at' => 'datetime',
    ];
}
