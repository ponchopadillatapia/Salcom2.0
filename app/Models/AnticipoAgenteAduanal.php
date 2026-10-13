<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnticipoAgenteAduanal extends Model
{
    protected $table = 'anticipos_agente_aduanal';

    protected $fillable = [
        'agente_aduanal_id',
        'factura_id',
        'operacion_id',
        'fecha',
        'importe',
        'moneda',
        'referencia',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'importe' => 'decimal:2',
    ];

    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteAduanal::class, 'agente_aduanal_id');
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(FacturaAgenteAduanal::class, 'factura_id');
    }

    public function operacion(): BelongsTo
    {
        return $this->belongsTo(OperacionComercioExterior::class, 'operacion_id');
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
