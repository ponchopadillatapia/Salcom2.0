<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoProveedorPrecio extends Model
{
    protected $table = 'producto_proveedor_precios';

    protected $fillable = [
        'producto_id',
        'proveedor_id',
        'precio',
        'moq',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'moq' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorUser::class, 'proveedor_id');
    }
}
