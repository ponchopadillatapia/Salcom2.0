<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbonoProveedorDocumento extends Model
{
    protected $table = 'abono_proveedor_documentos';

    protected $fillable = [
        'abono_id',
        'factura_id',
        'fecha_doc',
        'serie_doc',
        'folio_doc',
        'concepto_doc',
        'referencia',
        'importe_pago',
        'sistema_origen',
        'detalle',
    ];

    protected $casts = [
        'fecha_doc' => 'date',
        'importe_pago' => 'decimal:2',
        'detalle' => 'array',
    ];

    /** @return BelongsTo<AbonoProveedor, $this> */
    public function abono(): BelongsTo
    {
        return $this->belongsTo(AbonoProveedor::class, 'abono_id');
    }

    /** @return BelongsTo<Factura, $this> */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }
}
