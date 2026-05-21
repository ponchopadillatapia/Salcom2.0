<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read ProveedorUser|null $proveedor
 */
class OcBorrador extends Model
{
    protected $table = 'oc_borradores';

    protected $fillable = [
        'tipo', 'proveedor_id', 'productos', 'monto_estimado',
        'motivo', 'estatus', 'aprobada_por', 'aprobada_at', 'notas',
    ];

    protected $casts = [
        'productos' => 'array',
        'aprobada_at' => 'datetime',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorUser::class, 'proveedor_id');
    }

    public function scopePendientes($query)
    {
        return $query->where('estatus', 'pendiente');
    }
}
