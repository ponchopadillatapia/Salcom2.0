<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoAgenteAduanal extends Model
{
    protected $table = 'pagos_agente_aduanal';

    public const DIRECCIONES = [
        'salcom_paga_agente' => 'Salcom paga al agente',
        'agente_paga_salcom' => 'El agente paga a Salcom',
    ];

    protected $fillable = [
        'factura_id',
        'anticipo_id',
        'agente_aduanal_id',
        'fecha',
        'importe',
        'moneda',
        'direccion',
        'referencia',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'importe' => 'decimal:2',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(FacturaAgenteAduanal::class, 'factura_id');
    }

    public function anticipo(): BelongsTo
    {
        return $this->belongsTo(AnticipoAgenteAduanal::class, 'anticipo_id');
    }

    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteAduanal::class, 'agente_aduanal_id');
    }

    public function direccionLabel(): string
    {
        return self::DIRECCIONES[$this->direccion] ?? $this->direccion;
    }

    public function textoImporte(): string
    {
        $texto = '$'.number_format((float) $this->importe, 2);
        if ($this->moneda) {
            $texto .= ' '.$this->moneda;
        }

        return $texto;
    }
}
