<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExcelValidacion extends Model
{
    protected $table = 'excel_validaciones';

    protected $fillable = [
        'proveedor_id', 'archivo_path', 'total_productos',
        'productos_validos', 'productos_con_error', 'errores',
        'estatus', 'aprobado_por', 'aprobado_at',
    ];

    protected $casts = [
        'errores' => 'array',
        'aprobado_at' => 'datetime',
    ];

    public function proveedor()
    {
        return $this->belongsTo(ProveedorUser::class, 'proveedor_id');
    }
}
