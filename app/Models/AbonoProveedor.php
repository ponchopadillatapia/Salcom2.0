<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property-read ProveedorUser|null $proveedor
 * @property-read Collection<int, AbonoProveedorDocumento> $documentos
 */
class AbonoProveedor extends Model
{
    protected $table = 'abonos_proveedor';

    protected $fillable = [
        'poliza_key',
        'serie',
        'folio',
        'concepto',
        'fecha',
        'proveedor_id',
        'codigo_proveedor',
        'nombre_proveedor',
        'moneda',
        'tipo_cambio',
        'cuenta_bancaria',
        'estatus',
        'monto_pago',
        'notas',
        'creado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'tipo_cambio' => 'decimal:6',
        'monto_pago' => 'decimal:2',
    ];

    /** @return BelongsTo<ProveedorUser, $this> */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorUser::class, 'proveedor_id');
    }

    /** @return HasMany<AbonoProveedorDocumento, $this> */
    public function documentos(): HasMany
    {
        return $this->hasMany(AbonoProveedorDocumento::class, 'abono_id');
    }

    public function poliza(): ?array
    {
        return config('polizas_pago.'.$this->poliza_key);
    }

    public function etiquetaFolio(): string
    {
        return $this->serie.'-'.$this->folio;
    }

    public function esBorrador(): bool
    {
        return $this->estatus === 'borrador';
    }

    public function estaGuardado(): bool
    {
        return $this->estatus === 'guardado';
    }
}
