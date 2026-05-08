<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Auditoría.
 * Registra todas las acciones importantes del sistema para trazabilidad.
 */
class AuditLog extends Model
{
    protected $table = 'audit_log';

    protected $fillable = [
        'accion',
        'modulo',
        'usuario_tipo',
        'usuario_id',
        'usuario_nombre',
        'descripcion',
        'datos_antes',
        'datos_despues',
        'ip_address',
        'user_agent',
        'nivel',
    ];

    protected $casts = [
        'datos_antes' => 'array',
        'datos_despues' => 'array',
    ];
}
