<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read PagoProveedor|null $pago
 * @property-read Factura|null $factura
 */
class PagoProveedorFactura extends Model
{
    protected $table = 'pago_proveedor_facturas';

    protected $fillable = [
        'pago_id',
        'factura_id',
        'folio_cfdi',
        'uuid_cfdi',
        'es_fletera',
        'regimen_fiscal',
        'monto',
        'monto_iva',
        'retencion_iva',
        'retencion_isr',
        'total',
        'neto',
        'avisos',
    ];

    protected $casts = [
        'es_fletera' => 'boolean',
        'monto' => 'decimal:2',
        'monto_iva' => 'decimal:2',
        'retencion_iva' => 'decimal:2',
        'retencion_isr' => 'decimal:2',
        'total' => 'decimal:2',
        'neto' => 'decimal:2',
        'avisos' => 'array',
    ];

    /** @return BelongsTo<PagoProveedor, $this> */
    public function pago(): BelongsTo
    {
        return $this->belongsTo(PagoProveedor::class, 'pago_id');
    }

    /** @return BelongsTo<Factura, $this> */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }
}
