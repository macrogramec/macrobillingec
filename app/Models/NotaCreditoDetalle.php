<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para gestionar los detalles de las Notas de Crédito
 *
 * Este modelo maneja los detalles de productos o servicios incluidos en una nota de crédito,
 * incluyendo cantidades, precios e impuestos.
 *
 * @property int $id Identificador único del detalle
 * @property int $nota_credito_id ID de la nota de crédito relacionada
 * @property int|null $factura_detalle_id ID del detalle de factura original (si aplica)
 * @property int $linea Número de línea en el documento
 * @property string $codigoPrincipal Código principal del producto/servicio
 * @property string|null $codigoAuxiliar Código auxiliar del producto/servicio
 * @property string $descripcion Descripción del producto/servicio
 * @property float $cantidad Cantidad del producto/servicio
 * @property float $precioUnitario Precio unitario
 * @property float $descuento Valor del descuento
 * @property float $precioTotalSinImpuesto Precio total sin impuestos
 * @property string|null $unidadMedida Unidad de medida
 * @property string $version Versión del formato XML
 */
class NotaCreditoDetalle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     * @var string
     */
    protected $table = 'nota_credito_detalles';

    /**
     * Indica si el modelo debe registrar marcas de tiempo
     * @var bool
     */
    public $timestamps = true;

    /**
     * Campos que se pueden llenar masivamente
     * @var array
     */
    protected $fillable = [
        'nota_credito_id',
        'factura_detalle_id',
        'linea',
        'codigoPrincipal',
        'codigoAuxiliar',
        'descripcion',
        'cantidad',
        'precioUnitario',
        'descuento',
        'precioTotalSinImpuesto',
        'impuesto_codigo',
        'impuesto_codigoPorcentaje',
        'impuesto_tarifa',
        'impuesto_baseImponible',
        'impuesto_valor',
        'unidadMedida',
        'version',
        'detallesAdicionales'
    ];

    /**
     * Campos que deben ser convertidos a tipos nativos
     * @var array
     */
    protected $casts = [
        'cantidad' => 'float',
        'precioUnitario' => 'float',
        'descuento' => 'float',
        'precioTotalSinImpuesto' => 'float',
        'impuesto_tarifa' => 'float',
        'impuesto_baseImponible' => 'float',
        'impuesto_valor' => 'float',
        'detallesAdicionales' => 'array'
    ];

    /**
     * Relación con la nota de crédito
     */
    public function notaCredito(): BelongsTo
    {
        return $this->belongsTo(NotaCredito::class);
    }

    /**
     * Relación con el detalle de factura original
     */
    public function facturaDetalle(): BelongsTo
    {
        return $this->belongsTo(FacturaDetalle::class);
    }

    public function impuestos()
    {
        return $this->hasMany(NotaCreditoImpuesto::class);
    }

    /**
     * Calcula el subtotal del detalle (cantidad * precioUnitario)
     */
    public function calcularSubtotal(): float
    {
        return $this->cantidad * $this->precioUnitario;
    }

    /**
     * Calcula el total del detalle con descuento
     */
    public function calcularTotalConDescuento(): float
    {
        return $this->calcularSubtotal() - $this->descuento;
    }

    /**
     * Calcula el IVA del detalle
     */
    public function calcularIVA(): float
    {
        return $this->calcularTotalConDescuento() * ($this->impuesto_tarifa / 100);
    }

    /**
     * Calcula el total del detalle incluyendo impuestos
     */
    public function calcularTotal(): float
    {
        return $this->calcularTotalConDescuento() + $this->calcularIVA();
    }

    /**
     * Verifica si el detalle tiene descuento
     */
    public function tieneDescuento(): bool
    {
        return $this->descuento > 0;
    }

    /**
     * Obtiene el porcentaje de descuento aplicado
     */
    public function obtenerPorcentajeDescuento(): float
    {
        if ($this->calcularSubtotal() == 0) {
            return 0;
        }
        return ($this->descuento / $this->calcularSubtotal()) * 100;
    }

    /**
     * Valida la estructura del detalle
     */
    public function validarEstructura(): array
    {
        $errores = [];

        if ($this->cantidad <= 0) {
            $errores[] = 'La cantidad debe ser mayor a 0';
        }

        if ($this->precioUnitario < 0) {
            $errores[] = 'El precio unitario no puede ser negativo';
        }

        if ($this->descuento < 0) {
            $errores[] = 'El descuento no puede ser negativo';
        }

        if ($this->descuento > $this->calcularSubtotal()) {
            $errores[] = 'El descuento no puede ser mayor al subtotal';
        }

        return $errores;
    }
}
