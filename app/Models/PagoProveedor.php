<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property-read ProveedorUser|null $proveedor
 * @property-read Collection<int, PagoProveedorFactura> $lineas
 */
class PagoProveedor extends Model
{
    protected $table = 'pagos_proveedor';

    protected $fillable = [
        'proveedor_id',
        'codigo_proveedor',
        'tipo',
        'estatus',
        'fecha_pago',
        'notas',
        'comprobantes',
        'datos_confirmacion',
        'num_facturas',
        'monto_subtotal',
        'monto_iva',
        'monto_retencion_iva',
        'monto_retencion_isr',
        'monto_total',
        'monto_neto',
        'creado_por',
        'confirmado_por',
        'confirmado_at',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'confirmado_at' => 'datetime',
        'comprobantes' => 'array',
        'datos_confirmacion' => 'array',
        'monto_subtotal' => 'decimal:2',
        'monto_iva' => 'decimal:2',
        'monto_retencion_iva' => 'decimal:2',
        'monto_retencion_isr' => 'decimal:2',
        'monto_total' => 'decimal:2',
        'monto_neto' => 'decimal:2',
    ];

    /** @return BelongsTo<ProveedorUser, $this> */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorUser::class, 'proveedor_id');
    }

    /** @return HasMany<PagoProveedorFactura, $this> */
    public function lineas(): HasMany
    {
        return $this->hasMany(PagoProveedorFactura::class, 'pago_id');
    }

    public function esBorrador(): bool
    {
        return $this->estatus === 'borrador';
    }

    public function estaConfirmado(): bool
    {
        return $this->estatus === 'confirmado';
    }
}
