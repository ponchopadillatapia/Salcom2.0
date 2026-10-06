<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Estructura lista para ligar después un pedimento a un agente.
 * No hay módulo de pedimentos en el sistema: pedidos es otra cosa (ventas).
 */
class OperacionComercioExterior extends Model
{
    protected $table = 'operaciones_comercio_exterior';

    public const TIPOS = [
        'importacion' => 'Importación',
        'exportacion' => 'Exportación',
    ];

    protected $fillable = [
        'agente_aduanal_id',
        'aduana_id',
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

    public function aduana(): BelongsTo
    {
        return $this->belongsTo(Aduana::class, 'aduana_id');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo_operacion] ?? $this->tipo_operacion;
    }
}
