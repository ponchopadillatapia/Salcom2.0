<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnticipoProveedor extends Model
{
    use SoftDeletes;

    protected $table = 'anticipos_proveedor';

    protected $fillable = [
        'folio',
        'proveedor_id',
        'codigo_proveedor',
        'nombre_proveedor',
        'rfc_proveedor',
        'banco',
        'cuenta_banco',
        'clabe',
        'importe',
        'iva',
        'total_banco',
        'folio_general',
        'uuid_cfdi',
        'departamento',
        'fecha',
        'concepto',
        'estatus',
        'monto_aplicado',
        'factura_id',
        'creado_por',
        'datos',
    ];

    protected $casts = [
        'fecha' => 'date',
        'importe' => 'decimal:2',
        'iva' => 'decimal:2',
        'total_banco' => 'decimal:2',
        'monto_aplicado' => 'decimal:2',
        'datos' => 'array',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorUser::class, 'proveedor_id');
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function saldoPendiente(): float
    {
        return round((float) $this->total_banco - (float) $this->monto_aplicado, 2);
    }
}
