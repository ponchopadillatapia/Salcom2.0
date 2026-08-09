<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudModificacionDatos extends Model
{
    protected $table = 'solicitudes_modificacion_datos';

    protected $fillable = [
        'proveedor_id',
        'campo',
        'valor_actual',
        'valor_propuesto',
        'tipo_persona',
        'motivo',
        'estatus',
        'archivo_cif',
        'archivo_acta',
        'resultado_ia',
        'notas',
        'revisado_at',
    ];

    protected $casts = [
        'resultado_ia' => 'array',
        'revisado_at' => 'datetime',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorUser::class, 'proveedor_id');
    }

    public function esPendiente(): bool
    {
        return $this->estatus === 'pendiente';
    }
}
