<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgenteAduanal extends Model
{
    protected $table = 'agentes_aduanales';

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

    public function operaciones(): HasMany
    {
        return $this->hasMany(OperacionComercioExterior::class, 'agente_aduanal_id')->latest('fecha');
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(FacturaAgenteAduanal::class, 'agente_aduanal_id')->latest('fecha');
    }

    public function anticipos(): HasMany
    {
        return $this->hasMany(AnticipoAgenteAduanal::class, 'agente_aduanal_id')->latest('fecha');
    }

    public function nombreVisible(): string
    {
        $nombre = trim((string) $this->nombre);
        $agencia = trim((string) $this->agencia);

        if ($agencia !== '' && $nombre !== '' && strcasecmp($agencia, $nombre) !== 0) {
            return $agencia.' — '.$nombre;
        }

        return $nombre !== '' ? $nombre : ($agencia !== '' ? $agencia : '—');
    }
}
