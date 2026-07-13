<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ProveedorUser extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'proveedores_users';

    protected $fillable = [
        'usuario', 'password', 'id_proveedor', 'codigo_compras', 'nombre',
        'tipo_persona', 'telefono', 'correo', 'foto', 'activo',
        'score_entrega', 'score_puntualidad', 'score_total',
        'aviso_privacidad_aceptado', 'aviso_privacidad_fecha',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'activo' => 'boolean',
        'score_entrega' => 'decimal:2',
        'score_puntualidad' => 'decimal:2',
        'score_total' => 'decimal:2',
        'aviso_privacidad_aceptado' => 'boolean',
        'aviso_privacidad_fecha' => 'datetime',
    ];

    /**
     * Compatibilidad: si la columna id_proveedor no existe, usar codigo_compras.
     */
    public function getIdProveedorAttribute($value)
    {
        return $value ?? $this->attributes['codigo_compras'] ?? null;
    }

    /**
     * Nombre de la columna de código proveedor (compatibilidad producción/local).
     */
    public static function columnaCodigoProveedor(): string
    {
        static $col = null;
        if ($col === null) {
            $col = \Illuminate\Support\Facades\Schema::hasColumn('proveedores_users', 'id_proveedor')
                ? 'id_proveedor'
                : 'codigo_compras';
        }
        return $col;
    }

    /**
     * Scope para buscar por código de proveedor (compatible con ambas columnas).
     */
    public function scopeWhereCodigo($query, $operador, $valor = null)
    {
        if ($valor === null) {
            $valor = $operador;
            $operador = '=';
        }
        return $query->where(static::columnaCodigoProveedor(), $operador, $valor);
    }

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

    /**
     * Código visible para Compras (columna id_proveedor en BD).
     */
    public function idProveedorDisplay(): string
    {
        return $this->id_proveedor ?: '—';
    }

    /**
     * Etiqueta para selects admin: usa proveedor_id internamente, muestra ID Proveedor si existe.
     */
    public function opcionSelectLabel(): string
    {
        $nombre = $this->nombre ?? $this->usuario;
        $partes = [$nombre, '#'.$this->id];
        if ($this->id_proveedor) {
            $partes[] = 'ID '.$this->id_proveedor;
        }

        return implode(' · ', $partes);
    }
}
