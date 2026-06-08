<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para las migraciones masivas de productos.
 * Registra el progreso de cada carga masiva desde el sistema viejo.
 */
class MigracionMasiva extends Model
{
    protected $table = 'migraciones_masivas';

    protected $fillable = [
        'admin_id',
        'archivo_path',
        'total_productos',
        'productos_procesados',
        'productos_error',
        'lotes_total',
        'lotes_completados',
        'estatus',
        'resultado_path',
    ];

    protected $casts = [
        'estatus' => 'string',
        'total_productos' => 'integer',
        'productos_procesados' => 'integer',
        'productos_error' => 'integer',
        'lotes_total' => 'integer',
        'lotes_completados' => 'integer',
    ];

    /**
     * Admin que inició la migración.
     */
    public function admin()
    {
        return $this->belongsTo(AdminUser::class, 'admin_id');
    }

    /**
     * Porcentaje de progreso (0-100).
     */
    public function getPorcentajeAttribute(): int
    {
        if ($this->total_productos === 0) return 0;
        return (int) round(($this->productos_procesados + $this->productos_error) / $this->total_productos * 100);
    }
}
