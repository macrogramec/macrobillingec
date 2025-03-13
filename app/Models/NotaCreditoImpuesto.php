<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Modelo para gestionar los impuestos de las Notas de Crédito
 *
 * Este modelo maneja los impuestos aplicados a las notas de crédito,
 * incluyendo IVA, ICE, IRBPNR y sus cálculos respectivos.
 *
 * @property int $id Identificador único del impuesto
 * @property int $nota_credito_id ID de la nota de crédito relacionada
 * @property string $tipo_impuesto_codigo Código del tipo de impuesto
 * @property string $tarifa_codigo Código de la tarifa según catálogo SRI
 * @property float $base_imponible Base para el cálculo del impuesto
 * @property float $porcentaje Porcentaje del impuesto
 * @property float|null $valor_especifico Valor específico para impuestos fijos
 * @property float $valor Valor calculado del impuesto
 * @property float $valor_devuelto Valor que se está devolviendo/revirtiendo
 * @property bool $impuesto_retenido Indica si el impuesto fue retenido
 * @property int|null $factura_impuesto_id ID del impuesto en factura original
 * @property string $version Versión del formato XML
 */
class NotaCreditoImpuesto extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     * @var string
     */
    protected $table = 'nota_credito_impuestos';

    /**
     * Tipos de impuestos según el SRI
     */
    const TIPOS_IMPUESTO = [
        'IVA' => '2',
        'ICE' => '3',
        'IRBPNR' => '5'
    ];

    /**
     * Tarifas IVA actualizadas 2024
     */
    const TARIFAS_IVA = [
        '0' => '0',     // 0% (Productos y servicios con tarifa 0%)
        '2' => '15',    // 15% (Tarifa general desde abril 2024)
        '3' => '14',    // 14% (Histórico)
        '4' => '8',     // 8% (Servicios turísticos - temporal según decreto)
        '5' => '0',     // 0% (Transporte terrestre de pasajeros y carga)
        '6' => 'NO',    // No Objeto de Impuesto
        '7' => 'EX',    // Exento de IVA
        '8' => '0',     // 0% (Admisión a eventos deportivos)
        '9' => '5'      // 5% (Materiales y servicios de construcción - temporal según decreto)
    ];

    /**
     * Tipos de productos/servicios con tarifas especiales
     */
    const TIPOS_PRODUCTO = [
        'NORMAL' => ['codigo' => '2', 'tarifa' => 15],
        'MEDICINAS' => ['codigo' => '0', 'tarifa' => 0],
        'CANASTA_BASICA' => ['codigo' => '0', 'tarifa' => 0],
        'TRANSPORTE' => ['codigo' => '5', 'tarifa' => 0],
        'DEPORTES' => ['codigo' => '8', 'tarifa' => 0],
        'TURISMO' => ['codigo' => '4', 'tarifa' => 8],
        'CONSTRUCCION' => ['codigo' => '9', 'tarifa' => 5]
    ];

    /**
     * Tarifas ICE 2024
     */
    const TARIFAS_ICE = [
        // Productos del tabaco y sucedáneos
        '3011' => ['descripcion' => 'Productos del tabaco y sucedáneos', 'porcentaje' => 150],

        // Bebidas alcoholicas
        '3023' => ['descripcion' => 'Bebidas alcohólicas incluida la cerveza artesanal', 'porcentaje' => 75],
        '3610' => ['descripcion' => 'Cerveza Industrial', 'tipo' => 'MIXTO', 'porcentaje' => 75, 'especifico' => 13.20],

        // Bebidas no alcohólicas y gaseosas
        '3620' => ['descripcion' => 'Bebidas Gaseosas con contenido de azúcar menor o igual a 25g/litro', 'especifico' => 0.18],
        '3630' => ['descripcion' => 'Bebidas Gaseosas con contenido de azúcar mayor a 25g/litro', 'especifico' => 0.18],
        '3640' => ['descripcion' => 'Bebidas energizantes', 'porcentaje' => 10],

        // Perfumes y aguas de tocador
        '3072' => ['descripcion' => 'Perfumes y aguas de tocador', 'porcentaje' => 20],

        // Vehículos motorizados
        '3051' => ['descripcion' => 'Vehículos motorizados cuyo PVP superior a USD 70.000', 'porcentaje' => 35],

        // Fundas plásticas
        '3073' => ['descripcion' => 'Fundas plásticas', 'especifico' => 0.10],

        // Videojuegos
        '3074' => ['descripcion' => 'Videojuegos', 'porcentaje' => 35],

        // Armas de fuego y municiones
        '3075' => ['descripcion' => 'Armas de fuego, municiones', 'porcentaje' => 300],

        // Focos incandescentes
        '3076' => ['descripcion' => 'Focos incandescentes', 'porcentaje' => 100],
    ];

    /**
     * Tarifas IRBPNR 2024
     */
    const TARIFAS_IRBPNR = [
        '5001' => ['descripcion' => 'Botellas plásticas no retornables', 'valor' => 0.02],
    ];

    /**
     * Fechas de vigencia para tarifas especiales
     */
    const FECHAS_VIGENCIA = [
        'TURISMO' => [
            'inicio' => '2024-04-22',
            'fin' => '2024-12-31'
        ],
        'CONSTRUCCION' => [
            'inicio' => '2024-04-22',
            'fin' => '2024-12-31'
        ]
    ];

    /**
     * Campos que se pueden llenar masivamente
     * @var array
     */
    protected $fillable = [
        'nota_credito_id',
        'nota_credito_detalle_id',
        'tipo_impuesto_codigo',
        'tarifa_codigo',
        'base_imponible',
        'porcentaje',
        'valor_especifico',
        'valor',
        'valor_devuelto',
        'impuesto_retenido',
        'factura_impuesto_id',
        'version',
        'activo',
        'usuario_creacion',
        'usuario_modificacion'
    ];

    /**
     * Campos que deben ser convertidos a tipos nativos
     * @var array
     */
    protected $casts = [
        'base_imponible' => 'float',
        'porcentaje' => 'float',
        'valor_especifico' => 'float',
        'valor' => 'float',
        'valor_devuelto' => 'float',
        'impuesto_retenido' => 'boolean',
        'activo' => 'boolean'
    ];

    /**
     * Relación con la nota de crédito
     */
    public function notaCredito(): BelongsTo
    {
        return $this->belongsTo(NotaCredito::class);
    }

    /**
     * Relación con el impuesto de la factura original
     */
    public function facturaImpuesto(): BelongsTo
    {
        return $this->belongsTo(FacturaImpuesto::class);
    }

    public function detalle()
    {
        return $this->belongsTo(NotaCreditoDetalle::class, 'nota_credito_detalle_id');
    }

    /**
     * Valida y obtiene la tarifa según el tipo de producto
     */
    public function obtenerTarifaSegunProducto(string $tipoProducto): ?array
    {
        if ($this->tipo_impuesto_codigo === self::TIPOS_IMPUESTO['IVA']) {
            if (!$this->tarifaEspecialVigente($tipoProducto)) {
                return ['codigo' => '2', 'tarifa' => 15]; // Tarifa general por defecto
            }
            return self::TIPOS_PRODUCTO[$tipoProducto] ?? ['codigo' => '2', 'tarifa' => 15];
        }

        if ($this->tipo_impuesto_codigo === self::TIPOS_IMPUESTO['ICE']) {
            return self::TARIFAS_ICE[$this->tarifa_codigo] ?? null;
        }

        if ($this->tipo_impuesto_codigo === self::TIPOS_IMPUESTO['IRBPNR']) {
            return self::TARIFAS_IRBPNR[$this->tarifa_codigo] ?? null;
        }

        return null;
    }

    /**
     * Verifica si una tarifa especial está vigente
     */
    public function tarifaEspecialVigente(string $tipoProducto): bool
    {
        if (!isset(self::FECHAS_VIGENCIA[$tipoProducto])) {
            return true;
        }

        $hoy = Carbon::now();
        $inicio = Carbon::parse(self::FECHAS_VIGENCIA[$tipoProducto]['inicio']);
        $fin = Carbon::parse(self::FECHAS_VIGENCIA[$tipoProducto]['fin']);

        return $hoy->between($inicio, $fin);
    }

    /**
     * Calcula el valor del impuesto según su tipo y tarifa
     */
    public function calcularValor(): float
    {
        if (!is_null($this->valor_especifico)) {
            return round($this->valor_especifico, 2);
        }

        return round(($this->base_imponible * $this->porcentaje) / 100, 2);
    }

    /**
     * Obtiene la descripción del tipo de impuesto
     */
    public function getDescripcionTipoImpuesto(): string
    {
        $tipos = array_flip(self::TIPOS_IMPUESTO);
        return $tipos[$this->tipo_impuesto_codigo] ?? 'DESCONOCIDO';
    }

    /**
     * Obtiene la descripción de la tarifa
     */
    public function getDescripcionTarifa(): string
    {
        if ($this->tipo_impuesto_codigo === self::TIPOS_IMPUESTO['IVA']) {
            return self::TARIFAS_IVA[$this->tarifa_codigo] ?? 'DESCONOCIDO';
        }
        return $this->porcentaje . '%';
    }

    /**
     * Valida si el impuesto está correctamente calculado
     */
    public function validarCalculo(): bool
    {
        $calculado = $this->calcularValor();
        return abs($calculado - $this->valor) < 0.01;
    }

    /**
     * Crea un impuesto espejo para la nota de crédito desde una factura
     */
    public static function crearDesdeFacturaImpuesto(
        int $notaCreditoId,
        FacturaImpuesto $facturaImpuesto,
        float $baseImponible
    ): self {
        return static::create([
            'nota_credito_id' => $notaCreditoId,
            'tipo_impuesto_codigo' => $facturaImpuesto->tipo_impuesto_codigo,
            'tarifa_codigo' => $facturaImpuesto->tarifa_codigo,
            'base_imponible' => $baseImponible,
            'porcentaje' => $facturaImpuesto->porcentaje,
            'valor_especifico' => $facturaImpuesto->valor_especifico,
            'valor' => ($baseImponible * $facturaImpuesto->porcentaje) / 100,
            'factura_impuesto_id' => $facturaImpuesto->id,
            'version' => $facturaImpuesto->version
        ]);
    }

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->usuario_creacion = auth()->user()->name ?? 'Sistema';
        });

        static::updating(function ($model) {
            $model->usuario_modificacion = auth()->user()->name ?? 'Sistema';
        });
    }
}
