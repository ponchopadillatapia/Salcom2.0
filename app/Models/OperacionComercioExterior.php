<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OperacionComercioExterior extends Model
{
    protected $table = 'operaciones_comercio_exterior';

    public const TIPOS = [
        'importacion' => 'Importación',
        'exportacion' => 'Exportación',
    ];

    protected $fillable = [
        'agente_aduanal_id',
        'numero_patente',
        'numero_pedimento',
        'fecha',
        'contraparte',
        'mercancia',
        'tipo_operacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteAduanal::class, 'agente_aduanal_id');
    }

    public function factura(): HasOne
    {
        return $this->hasOne(FacturaAgenteAduanal::class, 'operacion_id');
    }

    public function anticipos(): HasMany
    {
        return $this->hasMany(AnticipoAgenteAduanal::class, 'operacion_id')->latest('fecha');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo_operacion] ?? ($this->tipo_operacion ?: '—');
    }
}
