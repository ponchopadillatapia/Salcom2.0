<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;

/**
 * @property-read Collection<int, ContactoProveedor> $contactos
 * @property-read Collection<int, DocumentoProveedor> $documentos
 * @property array|null $datos_identificacion
 */
class ProveedorUser extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'proveedores_users';

    protected $fillable = [
        'usuario', 'password', 'id_proveedor', 'codigo_compras', 'nombre',
        'tipo_persona', 'telefono', 'correo', 'foto', 'activo',
        'solicitud_alta_estatus', 'solicitud_alta_intentos',
        'datos_identificacion',
        'score_entrega', 'score_puntualidad', 'score_total',
        'aviso_privacidad_aceptado', 'aviso_privacidad_fecha',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'activo' => 'boolean',
        'datos_identificacion' => 'array',
        'solicitud_alta_intentos' => 'integer',
        'score_entrega' => 'decimal:2',
        'score_puntualidad' => 'decimal:2',
        'score_total' => 'decimal:2',
        'aviso_privacidad_aceptado' => 'boolean',
        'aviso_privacidad_fecha' => 'datetime',
    ];

    public const SOLICITUD_ALTA_MAX_INTENTOS = 5;

    /**
     * Compatibilidad: si la columna id_proveedor no existe, usar codigo_compras.
     */
    public function getIdProveedorAttribute($value)
    {
        if ($value !== null) {
            return $value;
        }

        return $this->attributes['codigo_compras'] ?? null;
    }

    /**
     * Nombre de la columna de código proveedor (compatibilidad producción/local).
     */
    public static function columnaCodigoProveedor(): string
    {
        static $col = null;
        if ($col === null) {
            try {
                $col = Schema::hasColumn('proveedores_users', 'id_proveedor')
                    ? 'id_proveedor'
                    : 'codigo_compras';
            } catch (\Exception $e) {
                $col = 'codigo_compras';
            }
        }

        return $col;
    }

    /**
     * Scope para buscar por código de proveedor (compatible con ambas columnas).
     */
    public function scopeWhereCodigo($query, $operador, $valor = null)
    {
        if ($valor === null) {
            $valor = $operador;
            $operador = '=';
        }

        return $query->where(static::columnaCodigoProveedor(), $operador, $valor);
    }

    public function contactos(): HasMany
    {
        return $this->hasMany(ContactoProveedor::class, 'proveedor_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoProveedor::class, 'proveedor_id');
    }

    /**
     * Calcula el score total: 50% entrega a tiempo + 50% puntualidad
     */
    public function calcularScore(): float
    {
        $this->score_total = ($this->score_entrega * 0.5) + ($this->score_puntualidad * 0.5);
        $this->save();

        return $this->score_total;
    }

    /**
     * Código visible para Compras (columna id_proveedor en BD).
     */
    public function idProveedorDisplay(): string
    {
        return $this->id_proveedor ?: '—';
    }

    /**
     * Etiqueta para selects admin: usa proveedor_id internamente, muestra ID Proveedor si existe.
     */
    public function opcionSelectLabel(): string
    {
        $nombre = $this->nombre ?? $this->usuario;
        $partes = [$nombre, '#'.$this->id];
        if ($this->id_proveedor) {
            $partes[] = 'ID '.$this->id_proveedor;
        }

        return implode(' · ', $partes);
    }

    /** Tipos de documentos fiscales requeridos según tipo de persona. */
    public function documentosRequeridos(): array
    {
        $esMoral = str_contains(strtolower((string) $this->tipo_persona), 'moral');

        if ($esMoral) {
            return [
                'cif' => 'CIF',
                'opinion' => 'Opinión SAT',
                'acta' => 'Acta constitutiva',
                'rep_legal' => 'INE Rep. legal',
                'contribuyente' => 'INE Contribuyente',
                'caratula_banco' => 'Carátula bancaria',
            ];
        }

        // Persona Física
        return [
            'cif' => 'CIF',
            'opinion' => 'Opinión SAT',
            'contribuyente' => 'INE Contribuyente',
            'caratula_banco' => 'Carátula bancaria',
        ];
    }

    public function tieneFormularioDatosBancarios(): bool
    {
        // Solo con datos bancarios reales en el formulario (no contar SolicitudAlta rechazada).
        $db = $this->datos_identificacion ?? [];
        if (! is_array($db)) {
            return false;
        }

        return ! empty(trim((string) ($db['banco'] ?? '')))
            || ! empty(trim((string) ($db['clabe'] ?? '')));
    }

    /** Hay formulario de identificación guardado (para revisión de Contabilidad/Dirección). */
    public function tieneFormularioIdentificacion(): bool
    {
        $db = $this->datos_identificacion;

        return is_array($db) && count(array_filter($db)) > 0;
    }

    /**
     * Intento actual a mostrar (1..5). Tras un rechazo, el siguiente reenvío es intentos+1.
     */
    public function solicitudAltaIntentoActual(): int
    {
        $usados = (int) ($this->solicitud_alta_intentos ?? 0);
        $estatus = $this->solicitud_alta_estatus ?? null;

        if ($estatus === 'rechazada') {
            return min($usados + 1, self::SOLICITUD_ALTA_MAX_INTENTOS);
        }

        if ($usados < 1) {
            return 1;
        }

        return min($usados, self::SOLICITUD_ALTA_MAX_INTENTOS);
    }

    public function solicitudAltaAgotoIntentos(): bool
    {
        return (int) ($this->solicitud_alta_intentos ?? 0) >= self::SOLICITUD_ALTA_MAX_INTENTOS
            && ($this->solicitud_alta_estatus ?? null) === 'rechazada';
    }

    /**
     * En revisión o ya aprobado: no puede reeditar bancarios/docs
     * (hasta que Dirección rechace o quede activo con renovación aparte).
     */
    public function onboardingEdicionBloqueada(): bool
    {
        if ($this->activo) {
            return true;
        }

        $estatus = $this->solicitud_alta_estatus ?? null;
        if ($estatus === 'aprobada') {
            return true;
        }

        if ($estatus === 'rechazada') {
            return false;
        }

        // Pendiente de Dirección con expediente completo → solo espera.
        return $this->listoParaDireccion();
    }

    public function documentosFiscalesCompletos(): bool
    {
        $docs = $this->relationLoaded('documentos')
            ? $this->documentos
            : $this->documentos()->get();

        foreach (array_keys($this->documentosRequeridos()) as $tipo) {
            /** @var DocumentoProveedor|null $doc */
            $doc = $docs->firstWhere('tipo', $tipo);
            if (! $doc instanceof DocumentoProveedor || $doc->estatus !== 'aprobado') {
                return false;
            }
        }

        return true;
    }

    /** Docs aprobados hace ≥14 días de un ciclo de 21 → avisar renovación (última semana). */
    public function documentosPorRenovar(): bool
    {
        if (! $this->documentosFiscalesCompletos()) {
            return false;
        }

        $docs = $this->relationLoaded('documentos')
            ? $this->documentos
            : $this->documentos()->get();

        foreach (array_keys($this->documentosRequeridos()) as $tipo) {
            /** @var DocumentoProveedor|null $doc */
            $doc = $docs->firstWhere('tipo', $tipo);
            if (! $doc instanceof DocumentoProveedor || $doc->estatus !== 'aprobado') {
                continue;
            }
            $desde = $doc->revisado_at ?? $doc->updated_at ?? $doc->created_at;
            if ($desde && now()->diffInDays($desde) >= 14) {
                return true;
            }
        }

        return false;
    }

    public function contactosSuficientes(): bool
    {
        return $this->contactos()->count() >= 2;
    }

    public function listoParaDireccion(): bool
    {
        return $this->tieneFormularioDatosBancarios()
            && $this->documentosFiscalesCompletos()
            && $this->contactosSuficientes();
    }
}
