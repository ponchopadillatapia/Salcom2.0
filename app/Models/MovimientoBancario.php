<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un movimiento del registro bancario WieseBanco (una fila, como en Quicken).
 *
 * payment = lo que sale (pago a proveedor), deposit = lo que entra.
 * memo = folios de las facturas que cubre el pago.
 * iddocumento_contpaqi = se llena cuando el pago se registra en Contpaqi vía la API C#.
 *
 * @property-read CuentaBancaria|null $cuenta
 */
class MovimientoBancario extends Model
{
    protected $table = 'movimientos_bancarios';

    protected $fillable = [
        'cuenta_id',
        'fecha',
        'num',
        'payee',
        'categoria',
        'memo',
        'payment',
        'deposit',
        'balance',
        'iddocumento_contpaqi',
        'folio_contpaqi',
        'codigo_proveedor',
        'estatus',
    ];

    protected $casts = [
        'fecha' => 'date',
        'num' => 'integer',
        'payment' => 'decimal:2',
        'deposit' => 'decimal:2',
        'balance' => 'decimal:2',
        'iddocumento_contpaqi' => 'integer',
        'folio_contpaqi' => 'integer',
    ];

    /** @return BelongsTo<CuentaBancaria, $this> */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_id');
    }
}
