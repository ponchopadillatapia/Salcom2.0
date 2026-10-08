<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Cuenta bancaria del registro WieseBanco (reemplazo de Quicken).
 *
 * Cada cuenta lleva su propio consecutivo (columna NUM en Quicken). De aquí
 * nace el folio que se le manda a Contpaqi al registrar un pago.
 *
 * @property-read \Illuminate\Support\Collection<int, MovimientoBancario> $movimientos
 */
class CuentaBancaria extends Model
{
    protected $table = 'cuentas_bancarias';

    protected $fillable = [
        'nombre',
        'banco',
        'clave_corta',
        'concepto_contpaqi',
        'consecutivo_actual',
        'saldo_actual',
        'activo',
    ];

    protected $casts = [
        'consecutivo_actual' => 'integer',
        'saldo_actual' => 'decimal:2',
        'activo' => 'boolean',
    ];

    /** @return HasMany<MovimientoBancario, $this> */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoBancario::class, 'cuenta_id');
    }

    /**
     * Devuelve el siguiente folio (NUM) de esta cuenta y lo reserva de forma segura.
     *
     * POR QUÉ con transacción + lockForUpdate: si dos pagos ocurren casi al mismo
     * tiempo, sin bloqueo podrían tomar el MISMO número (ej. los dos 80195). El
     * lockForUpdate "aparta" la fila de la cuenta hasta terminar, así cada pago
     * obtiene un número distinto y consecutivo, igual que lo hacía Quicken.
     *
     * No recibe el valor por fuera: lee el consecutivo_actual real de la BD,
     * le suma 1, lo guarda y devuelve el nuevo. Es la ÚNICA fuente del folio.
     */
    public function siguienteFolio(): int
    {
        return DB::transaction(function () {
            // Relee ESTA cuenta con bloqueo para que nadie más la toque mientras tanto.
            $cuenta = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            $nuevo = (int) $cuenta->consecutivo_actual + 1;
            $cuenta->consecutivo_actual = $nuevo;
            $cuenta->save();

            // Mantener en memoria el objeto actual sincronizado con lo guardado.
            $this->consecutivo_actual = $nuevo;

            return $nuevo;
        });
    }
}
