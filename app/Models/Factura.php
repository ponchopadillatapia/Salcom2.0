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
        // Visto bueno visual del admin (flujo de expediente, separado del estatus de pago).
        'revision_admin',
        'revision_notas',
        'revisado_por',
        'revisado_por_nombre',
        'revisado_admin_at',
        'visto_admin_at',
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
        'revisado_admin_at' => 'datetime',
        'visto_admin_at' => 'datetime',
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
    public static function diasHastaVencimiento(Carbon|string|null $fecha): ?int
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        $hoy = now()->startOfDay();
        $vence = $fecha instanceof Carbon
            ? $fecha->copy()->startOfDay()
            : Carbon::parse($fecha)->startOfDay();
        $segundos = $vence->getTimestamp() - $hoy->getTimestamp();

        return (int) round($segundos / 86400);
    }

    public function diasRestantes(): ?int
    {
        // Si ya está pagada, 0 días restantes
        if (in_array($this->estatus, ['pagada'])) {
            return 0;
        }
        return self::diasHastaVencimiento($this->fecha_vencimiento);
    }

    /**
     * Deriva el estatus de pago a partir de un documento de Wiese (la FUENTE DE VERDAD).
     *
     * POR QUÉ existe (decisión de Said/Brenda): "Wiese manda". El sistema local está en desarrollo,
     * así que la verdad sobre si una factura está pagada o pendiente la dice Wiese, NO el estatus
     * local. Esta regla estaba COPIADA en 4+ lugares (controladores y vistas); aquí queda en UN solo
     * sitio para que no se desincronice.
     *
     * Regla (confirmada con Alan):
     *   ccancelado == 1   -> 'cancelada'
     *   cpendiente  > 0   -> 'pendiente'  (todavía debe saldo)
     *   cpendiente == 0   -> 'pagada'     (saldo cubierto)
     *
     * Acepta tanto los campos crudos de Wiese (ccancelado/cpendiente) como los ya mapeados
     * (saldo), para servir a cualquier pantalla.
     *
     * @param  array<string,mixed>  $doc  item de Wiese (factura u OC)
     */
    public static function estatusDesdeWiese(array $doc): string
    {
        if ((int) ($doc['ccancelado'] ?? 0) === 1) {
            return 'cancelada';
        }

        // Saldo pendiente: Wiese lo manda como 'cpendiente' (crudo) o 'saldo' (ya mapeado).
        $saldo = (float) ($doc['cpendiente'] ?? $doc['saldo'] ?? 0);

        return $saldo > 0 ? 'pendiente' : 'pagada';
    }
}
