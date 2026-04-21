<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ProveedorUser extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'proveedores_users';

    protected $fillable = [
        'usuario', 'password', 'codigo_compras', 'nombre',
        'tipo_persona', 'telefono', 'correo', 'activo',
        'score_entrega', 'score_puntualidad', 'score_total',
        'aviso_privacidad_aceptado', 'aviso_privacidad_fecha',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'activo'                    => 'boolean',
        'score_entrega'             => 'decimal:2',
        'score_puntualidad'         => 'decimal:2',
        'score_total'               => 'decimal:2',
        'aviso_privacidad_aceptado' => 'boolean',
        'aviso_privacidad_fecha'    => 'datetime',
    ];

    public function contactos()
    {
        return $this->hasMany(ContactoProveedor::class, 'proveedor_id');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoProveedor::class, 'proveedor_id');
    }

    /**
     * Calcula el score total: 50% entrega a tiempo + 50% puntualidad
     */
    public function calcularScore(): float
    {
        $this->score_total = ($this->score_entrega * 0.5) + ($this->score_puntualidad * 0.5);
        $this->save();
        return $this->score_total;
    }
}
