<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ClienteUser extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'clientes_users';

    protected $fillable = [
        'nombre', 'correo', 'usuario', 'password', 'telefono',
        'rfc', 'tipo_persona', 'codigo_cliente', 'tipo_cliente',
        'credito_autorizado', 'limite_credito', 'activo',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'activo' => 'boolean',
        'credito_autorizado' => 'boolean',
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'codigo_cliente', 'codigo_cliente');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'codigo_cliente', 'codigo_cliente');
    }

    public function encuestas()
    {
        return $this->hasMany(Encuesta::class, 'codigo_cliente', 'codigo_cliente');
    }
}
