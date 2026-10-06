<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoAgenteAduanal extends Model
{
    protected $table = 'documentos_agente_aduanal';

    public const TIPOS = [
        'patente' => 'Patente aduanal',
        'identificacion' => 'Identificación',
        'constancia' => 'Constancia',
        'contrato' => 'Contrato o convenio',
        'acuse' => 'Acuse',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'agente_aduanal_id',
        'tipo',
        'nombre_archivo',
        'ruta',
        'fecha_carga',
        'fecha_vencimiento',
        'observaciones',
        'subido_por',
    ];

    protected $casts = [
        'fecha_carga' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteAduanal::class, 'agente_aduanal_id');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
