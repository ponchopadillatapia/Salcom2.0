<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read ProveedorUser|null $proveedor
 * @property-read int|null $cantidad Alias de agregaciones (count) en consultas selectRaw
 * @property-read int|null $num_facturas
 * @property-read float|string|null $monto_total
 * @property-read string|null $ultima_factura
 * @property-read string|Carbon|null $ultima_factura_at
 * @property array|null $avisos_pago
 * @property float|null $neto_pago
 * @property string|null $folio_display
 */
class Factura extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'folio_cfdi',
        'uuid_cfdi',
        'codigo_cliente',
        'codigo_proveedor',
        'regimen_fiscal',
        'es_fletera',
        'pedido_id',
        'monto',
        'monto_iva',
        'retencion_iva',
        'retencion_isr',
        'total',
        'monto_pagado',
        'estatus',
        'fecha_vencimiento',
        'dias_plazo',
        'archivo_pdf',
        'archivo_xml',
        'archivo_oc',
        'notas',
        'validacion_detalle',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'monto_iva' => 'decimal:2',
        'retencion_iva' => 'decimal:2',
        'retencion_isr' => 'decimal:2',
        'total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'es_fletera' => 'boolean',
        'fecha_vencimiento' => 'date',
        'dias_plazo' => 'integer',
        'validacion_detalle' => 'array',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function cliente()
    {
        return $this->belongsTo(ClienteUser::class, 'codigo_cliente', 'codigo_cliente');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorUser::class, 'codigo_proveedor', 'id_proveedor');
    }

    /**
     * Días que faltan para el vencimiento. Baja solo cada día
     * (320 hoy → 319 mañana) a partir de fecha_vencimiento.
     */
    public function diasRestantes(): ?int
    {
        if (! $this->fecha_vencimiento) {
            return null;
        }

        $hoy = now()->startOfDay();
        $vence = $this->fecha_vencimiento->copy()->startOfDay();
        $segundos = $vence->getTimestamp() - $hoy->getTimestamp();

        return (int) round($segundos / 86400);
    }
}
