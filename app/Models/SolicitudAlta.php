<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read ProveedorUser|null $proveedor
 */
class SolicitudAlta extends Model
{
    protected $table = 'solicitudes_alta';

    protected $fillable = [
        'proveedor_id', 'tipo_persona', 'nombre_completo', 'razon_social',
        'apellido_paterno', 'apellido_materno', 'nombres',
        'calle', 'num_exterior', 'num_interior', 'colonia', 'municipio',
        'estado', 'ciudad', 'pais', 'cp', 'telefono', 'celular',
        'telefono2', 'extension', 'correo', 'clabe', 'cuenta', 'banco',
        'docs_marcados', 'nombre_firma', 'estatus', 'notas_admin',
    ];

    protected $casts = [
        'docs_marcados' => 'array',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorUser::class, 'proveedor_id');
    }
}
