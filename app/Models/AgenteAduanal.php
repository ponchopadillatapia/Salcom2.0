<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgenteAduanal extends Model
{
    protected $table = 'agentes_aduanales';

    public const TIPOS_OPERACION = [
        'importacion' => 'Importación',
        'exportacion' => 'Exportación',
        'ambas' => 'Ambas',
    ];

    public const VERIFICACIONES = [
        'no_verificado' => 'No verificado',
        'verificado' => 'Verificado',
        'no_localizado' => 'No localizado en la fuente',
    ];

    public const FUENTES = [
        'sat' => 'SAT — Padrón de Agentes y Apoderados Aduanales',
        'anam' => 'ANAM',
        'caaarem' => 'CAAAREM — Directorio de Agentes Aduanales',
        'otra' => 'Otra fuente',
    ];

    protected $fillable = [
        'nombre',
        'rfc',
        'numero_patente',
        'agencia',
        'tipo_operacion',
        'contacto_nombre',
        'contacto_correo',
        'contacto_telefono',
        'contacto_celular',
        'activo',
        'estado_verificacion',
        'fecha_ultima_verificacion',
        'fuente_verificacion',
        'fuente_detalle',
        'observaciones',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_ultima_verificacion' => 'date',
    ];

    public function aduanas(): BelongsToMany
    {
        return $this->belongsToMany(Aduana::class, 'agente_aduanal_aduana', 'agente_aduanal_id', 'aduana_id')
            ->withTimestamps()
            ->orderBy('clave');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoAgenteAduanal::class, 'agente_aduanal_id')->latest('fecha_carga');
    }

    public function encargos(): HasMany
    {
        return $this->hasMany(EncargoConferido::class, 'agente_aduanal_id')->latest('fecha_inicio');
    }

    public function operaciones(): HasMany
    {
        return $this->hasMany(OperacionComercioExterior::class, 'agente_aduanal_id')->latest('fecha');
    }

    public function tipoOperacionLabel(): string
    {
        return self::TIPOS_OPERACION[$this->tipo_operacion] ?? $this->tipo_operacion;
    }

    public function verificacionLabel(): string
    {
        return self::VERIFICACIONES[$this->estado_verificacion] ?? $this->estado_verificacion;
    }

    public function fuenteLabel(): ?string
    {
        if (! $this->fuente_verificacion) {
            return null;
        }

        $fuente = self::FUENTES[$this->fuente_verificacion] ?? $this->fuente_verificacion;
        if ($this->fuente_verificacion === 'otra' && $this->fuente_detalle) {
            return $this->fuente_detalle;
        }

        return $fuente;
    }
}
