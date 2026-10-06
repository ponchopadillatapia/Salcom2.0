<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncargoConferido extends Model
{
    protected $table = 'encargos_conferidos';

    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'aceptado' => 'Aceptado',
        'rechazado' => 'Rechazado',
        'revocado' => 'Revocado',
        'vencido' => 'Vencido',
    ];

    protected $fillable = [
        'agente_aduanal_id',
        'numero_patente',
        'fecha_inicio',
        'fecha_termino',
        'estado',
        'numero_acuse',
        'nombre_acuse',
        'ruta_acuse',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_termino' => 'date',
    ];

    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteAduanal::class, 'agente_aduanal_id');
    }

    /**
     * Si la fecha de término capturada ya pasó y el encargo sigue aceptado,
     * se muestra como vencido. Es un cálculo interno: no proviene del SAT.
     */
    public function estadoEfectivo(): string
    {
        if ($this->estado === 'aceptado' && $this->fechaTerminoVencida()) {
            return 'vencido';
        }

        return $this->estado;
    }

    public function fechaTerminoVencida(): bool
    {
        return $this->fecha_termino !== null
            && $this->fecha_termino->copy()->startOfDay()->lt(now()->startOfDay());
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estadoEfectivo()] ?? $this->estadoEfectivo();
    }
}
