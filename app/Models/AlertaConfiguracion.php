<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaConfiguracion extends Model
{
    protected $table = 'alerta_configuracion';

    protected $fillable = ['clave', 'valor', 'descripcion', 'updated_by'];

    /**
     * Obtener valor de configuración por clave.
     */
    public static function get(string $clave, $default = null): mixed
    {
        $config = static::where('clave', $clave)->first();

        return $config ? $config->valor : $default;
    }

    /**
     * Establecer valor de configuración.
     */
    public static function set(string $clave, string $valor, ?int $userId = null): void
    {
        static::updateOrCreate(
            ['clave' => $clave],
            ['valor' => $valor, 'updated_by' => $userId]
        );
    }
}
