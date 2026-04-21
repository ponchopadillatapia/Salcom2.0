<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactoProveedor extends Model
{
    protected $table = 'contactos_proveedor';

    protected $fillable = [
        'proveedor_id', 'nombre', 'rol', 'telefono', 'correo',
    ];

    public function proveedor()
    {
        return $this->belongsTo(ProveedorUser::class, 'proveedor_id');
    }
}
