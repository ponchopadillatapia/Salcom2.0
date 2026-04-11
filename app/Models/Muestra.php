<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Muestra extends Model
{
    protected $fillable = [
        'lote', 'producto', 'proveedor', 'proveedor_contacto',
        'descripcion', 'cantidad', 'unidad', 'etapa',
        'fecha_registro', 'fecha_recepcion', 'fecha_validacion',
        'fecha_laboratorio', 'fecha_piso', 'fecha_estabilidad',
        'fecha_resolucion', 'dias_validacion', 'notas', 'motivo_rechazo',
    ];

    protected $casts = [
        'fecha_registro'    => 'datetime',
        'fecha_recepcion'   => 'datetime',
        'fecha_validacion'  => 'datetime',
        'fecha_laboratorio' => 'datetime',
        'fecha_piso'        => 'datetime',
        'fecha_estabilidad' => 'datetime',
        'fecha_resolucion'  => 'datetime',
    ];

    /**
     * Etapas en orden del proceso.
     */
    public const ETAPAS = [
        'registro'     => ['label' => 'Registro de Lote',      'icono' => 'bi-box-seam',        'dias' => 0],
        'recepcion'    => ['label' => 'Recepción',             'icono' => 'bi-inbox',           'dias' => 1],
        'validacion'   => ['label' => 'Validación (15-20 días)', 'icono' => 'bi-hourglass-split', 'dias' => 15],
        'laboratorio'  => ['label' => 'Pruebas de Laboratorio','icono' => 'bi-eyedropper',      'dias' => 5],
        'piso'         => ['label' => 'Pruebas de Piso',       'icono' => 'bi-gear',            'dias' => 5],
        'estabilidad'  => ['label' => 'Pruebas de Estabilidad','icono' => 'bi-thermometer-half','dias' => 5],
        'aprobado'     => ['label' => 'Alta Material Aprobado','icono' => 'bi-check-circle',    'dias' => 0],
        'rechazado'    => ['label' => 'Rechazado',             'icono' => 'bi-x-circle',        'dias' => 0],
    ];

    /**
     * Avanza automáticamente la etapa según las fechas.
     */
    public function avanzarEtapa(): void
    {
        if (in_array($this->etapa, ['aprobado', 'rechazado'])) return;

        $ahora = Carbon::now();
        $orden = ['registro', 'recepcion', 'validacion', 'laboratorio', 'piso', 'estabilidad'];
        $indice = array_search($this->etapa, $orden);

        if ($indice === false) return;

        // Verificar si ya pasó el tiempo de la etapa actual
        $campoFecha = 'fecha_' . $this->etapa;
        $fechaInicio = $this->{$campoFecha};

        if (!$fechaInicio) {
            // Si no tiene fecha de inicio, asignarla ahora
            $this->{$campoFecha} = $ahora;
            $this->save();
            return;
        }

        $diasEtapa = self::ETAPAS[$this->etapa]['dias'];
        if ($this->etapa === 'validacion') {
            $diasEtapa = $this->dias_validacion;
        }

        $fechaFin = Carbon::parse($fechaInicio)->addDays($diasEtapa);

        if ($ahora->gte($fechaFin) && isset($orden[$indice + 1])) {
            $siguienteEtapa = $orden[$indice + 1];
            $this->etapa = $siguienteEtapa;
            $this->{'fecha_' . $siguienteEtapa} = $ahora;
            $this->save();

            // Recursivo: verificar si también ya pasó la siguiente
            $this->avanzarEtapa();
        }
    }

    public function getEtapaLabelAttribute(): string
    {
        return self::ETAPAS[$this->etapa]['label'] ?? $this->etapa;
    }

    public function getEtapaIconoAttribute(): string
    {
        return self::ETAPAS[$this->etapa]['icono'] ?? 'bi-circle';
    }

    public function getProgresoAttribute(): int
    {
        $orden = ['registro', 'recepcion', 'validacion', 'laboratorio', 'piso', 'estabilidad', 'aprobado'];
        $indice = array_search($this->etapa, $orden);
        if ($this->etapa === 'rechazado') return 0;
        return $indice !== false ? (int) round(($indice / (count($orden) - 1)) * 100) : 0;
    }
}
