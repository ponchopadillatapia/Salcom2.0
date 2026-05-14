<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    protected $fillable = [
        'tipo', 'modulo', 'destinatario_tipo', 'destinatario_id',
        'titulo', 'contenido', 'datos', 'canal_enviado',
        'estatus', 'nivel', 'leida_at', 'accionada_at',
    ];

    protected $casts = [
        'datos' => 'array',
        'leida_at' => 'datetime',
        'accionada_at' => 'datetime',
    ];

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estatus', 'pendiente');
    }

    public function scopeParaUsuario($query, string $tipo, int $id)
    {
        return $query->where('destinatario_tipo', $tipo)->where('destinatario_id', $id);
    }

    public function scopeCriticas($query)
    {
        return $query->where('nivel', 'critical');
    }
}
