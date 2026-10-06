<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Aduana extends Model
{
    protected $table = 'aduanas';

    protected $fillable = [
        'clave',
        'nombre',
        'entidad',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function agentes(): BelongsToMany
    {
        return $this->belongsToMany(AgenteAduanal::class, 'agente_aduanal_aduana', 'aduana_id', 'agente_aduanal_id')
            ->withTimestamps();
    }

    public function etiqueta(): string
    {
        $lugar = $this->entidad ? $this->nombre.', '.$this->entidad : $this->nombre;

        return $this->clave ? $this->clave.' — '.$lugar : $lugar;
    }
}
