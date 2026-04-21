<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoProveedor extends Model
{
    protected $table = 'documentos_proveedor';

    protected $fillable = [
        'proveedor_id', 'tipo', 'archivo', 'estatus',
        'resultado_validacion', 'notas_revision', 'revisado_at',
    ];

    protected $casts = [
        'resultado_validacion' => 'array',
        'revisado_at'          => 'datetime',
    ];

    public function proveedor()
    {
        return $this->belongsTo(ProveedorUser::class, 'proveedor_id');
    }
}
