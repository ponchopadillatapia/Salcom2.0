<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacturaAgenteAduanal extends Model
{
    protected $table = 'facturas_agente_aduanal';

    public const MONEDAS = [
        'MXN' => 'MXN',
        'USD' => 'USD',
    ];

    public const ESTADOS_PAGO = [
        'pendiente' => 'Pendiente',
        'parcial' => 'Parcial',
        'pagado' => 'Pagado',
    ];

    public const ESTADOS_CANCELACION = [
        'vigente' => 'Vigente',
        'cancelada' => 'Cancelada',
    ];

    public const SENTIDOS = [
        'salcom_paga_agente' => 'Salcom paga al agente',
        'agente_paga_salcom' => 'El agente paga a Salcom',
    ];

    protected $fillable = [
        'operacion_id',
        'agente_aduanal_id',
        'folio',
        'serie',
        'fecha',
        'moneda',
        'importe',
        'saldo_pendiente',
        'honorarios',
        'impuestos',
        'retenciones',
        'estado_cancelacion',
        'estado_pago',
        'sentido',
        'nombre_archivo',
        'ruta',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'importe' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'honorarios' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'retenciones' => 'decimal:2',
    ];

    public function operacion(): BelongsTo
    {
        return $this->belongsTo(OperacionComercioExterior::class, 'operacion_id');
    }

    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteAduanal::class, 'agente_aduanal_id');
    }

    public function anticipos(): HasMany
    {
        return $this->hasMany(AnticipoAgenteAduanal::class, 'factura_id')->latest('fecha');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoAgenteAduanal::class, 'factura_id')->latest('fecha');
    }

    public function serieFolio(): string
    {
        $serie = trim((string) $this->serie);
        $folio = trim((string) $this->folio);

        if ($serie !== '' && $folio !== '') {
            return $serie.'-'.$folio;
        }

        return $folio !== '' ? $folio : ($serie !== '' ? $serie : '—');
    }

    public function textoMonto(?string $campo): string
    {
        $valor = $this->getAttribute($campo);
        if ($valor === null || $valor === '') {
            return '—';
        }

        $texto = '$'.number_format((float) $valor, 2);
        if ($this->moneda) {
            $texto .= ' '.$this->moneda;
        }

        return $texto;
    }

    public function estadoPagoLabel(): string
    {
        return self::ESTADOS_PAGO[$this->estado_pago] ?? ($this->estado_pago ?: '—');
    }

    public function estadoCancelacionLabel(): string
    {
        return self::ESTADOS_CANCELACION[$this->estado_cancelacion] ?? ($this->estado_cancelacion ?: '—');
    }

    public function sentidoLabel(): string
    {
        if (! $this->sentido) {
            return 'Por definir';
        }

        return self::SENTIDOS[$this->sentido] ?? $this->sentido;
    }
}
