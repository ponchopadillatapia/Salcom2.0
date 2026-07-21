<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read ProveedorUser|null $proveedor
 * @property-read int|null $cantidad Alias de agregaciones (count) en consultas selectRaw
 * @property-read int|null $num_facturas
 * @property-read float|string|null $monto_total
 * @property-read string|null $ultima_factura
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
        'estatus',
        'fecha_vencimiento',
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
        'es_fletera' => 'boolean',
        'fecha_vencimiento' => 'date',
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
}
