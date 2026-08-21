<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReembolsoViaje extends Model
{
    protected $table = 'reembolsos_viaje';

    protected $fillable = [
        'codigo_empleado', 'nombre_empleado', 'departamento',
        'pais_destino', 'moneda_destino', 'tipo_cambio', 'moneda_base',
        'gastos', 'total_moneda_local', 'total_moneda_base',
        'estatus', 'archivo_comprobantes', 'notas', 'notas_revision',
        'enviado_at', 'aprobado_at', 'aprobado_por',
    ];

    protected $casts = [
        'gastos' => 'array',
        'tipo_cambio' => 'decimal:4',
        'total_moneda_local' => 'decimal:2',
        'total_moneda_base' => 'decimal:2',
        'enviado_at' => 'datetime',
        'aprobado_at' => 'datetime',
    ];

    public const ESTATUS_BORRADOR = 'borrador';
    public const ESTATUS_ENVIADO = 'enviado';
    public const ESTATUS_APROBADO = 'aprobado';
    public const ESTATUS_RECHAZADO = 'rechazado';
    public const ESTATUS_REEMBOLSADO = 'reembolsado';

    public const PAISES_MONEDA = [
        'Colombia' => ['moneda' => 'COP', 'simbolo' => '$'],
        'Ecuador' => ['moneda' => 'USD', 'simbolo' => '$'],
        'Estados Unidos' => ['moneda' => 'USD', 'simbolo' => '$'],
        'Europa (Zona Euro)' => ['moneda' => 'EUR', 'simbolo' => '€'],
        'China' => ['moneda' => 'CNY', 'simbolo' => '¥'],
        'Brasil' => ['moneda' => 'BRL', 'simbolo' => 'R$'],
        'Chile' => ['moneda' => 'CLP', 'simbolo' => '$'],
        'Perú' => ['moneda' => 'PEN', 'simbolo' => 'S/'],
        'Argentina' => ['moneda' => 'ARS', 'simbolo' => '$'],
        'Canadá' => ['moneda' => 'CAD', 'simbolo' => 'CA$'],
        'Reino Unido' => ['moneda' => 'GBP', 'simbolo' => '£'],
        'Japón' => ['moneda' => 'JPY', 'simbolo' => '¥'],
        'México (nacional)' => ['moneda' => 'MXN', 'simbolo' => '$'],
    ];

    public const CONCEPTOS_GASTO = [
        'hospedaje' => 'Hospedaje',
        'alimentacion' => 'Alimentación',
        'transporte' => 'Transporte',
        'imprevistos' => 'Imprevistos',
        'comunicaciones' => 'Comunicaciones',
        'otro' => 'Otro',
    ];

    public function estaEditable(): bool
    {
        return $this->estatus === self::ESTATUS_BORRADOR;
    }

    public function badgeClass(): string
    {
        return match ($this->estatus) {
            self::ESTATUS_BORRADOR => 'badge-borrador',
            self::ESTATUS_ENVIADO => 'badge-pendiente',
            self::ESTATUS_APROBADO => 'badge-aprobado',
            self::ESTATUS_RECHAZADO => 'badge-rechazado',
            self::ESTATUS_REEMBOLSADO => 'badge-pagado',
            default => '',
        };
    }

    public function estatusLabel(): string
    {
        return match ($this->estatus) {
            self::ESTATUS_BORRADOR => 'Borrador',
            self::ESTATUS_ENVIADO => 'Enviado',
            self::ESTATUS_APROBADO => 'Aprobado',
            self::ESTATUS_RECHAZADO => 'Rechazado',
            self::ESTATUS_REEMBOLSADO => 'Reembolsado',
            default => ucfirst($this->estatus),
        };
    }
}
