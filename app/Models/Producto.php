<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'codigo', 'codigo_alterno', 'nombre', 'nombre_alterno',
        'nombre_tipo', 'nombre_marca', 'nombre_modelo', 'nombre_medida', 'nombre_especificacion',
        'clave_sat', 'descripcion_corta', 'descripcion',
        'categoria', 'familia', 'subfamilia', 'segmento_mercado', 'tipo_producto',
        'precio', 'unidad_venta', 'stock',
        'cajas_por_tarima', 'peso_bruto_caja', 'peso_bruto', 'piezas_por_caja', 'volumen',
        'maneja_lotes', 'unidad_xml', 'iva', 'ieps', 'foto', 'activo', 'proveedor_nombre', 'proveedor_tipo',
        'departamento', 'linea', 'subfamilia_pt', 'canal', 'vendedor', 'modulo',
    ];

    public function preciosProveedor(): HasMany
    {
        return $this->hasMany(ProductoProveedorPrecio::class);
    }

    public function precioParaProveedor(?int $proveedorId): ?float
    {
        if (! $proveedorId) {
            return (float) $this->precio;
        }

        $registro = $this->preciosProveedor()->where('proveedor_id', $proveedorId)->first();

        return $registro ? (float) $registro->precio : (float) $this->precio;
    }

    public function moqParaProveedor(?int $proveedorId): ?int
    {
        if (! $proveedorId) {
            return null;
        }

        return $this->preciosProveedor()->where('proveedor_id', $proveedorId)->value('moq');
    }

    protected $casts = [
        'activo' => 'boolean',
        'maneja_lotes' => 'boolean',
        'precio' => 'decimal:2',
        'peso_bruto_caja' => 'decimal:4',
        'peso_bruto' => 'decimal:4',
        'piezas_por_caja' => 'decimal:2',
        'volumen' => 'decimal:7',
        'iva' => 'decimal:2',
        'ieps' => 'decimal:2',
    ];
}
