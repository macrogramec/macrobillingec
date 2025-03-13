<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CodigoRetencion;
use Carbon\Carbon;

class CodigoRetencionSeeder extends Seeder
{
    public function run(): void
    {
        $codigosIR = [
            // Retenciones en la Fuente del Impuesto a la Renta
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '301',
                'concepto' => 'Pagos efectuados al exterior sin convenio de doble tributación por concepto de cánones, regalías, servicios técnicos, administrativos y de consultoría.',
                'porcentaje' => 25.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '303',
                'concepto' => 'Honorarios profesionales y demás pagos por servicios relacionados con el título profesional',
                'porcentaje' => 10.00,
                'categoria' => 'normal',
                'tipo_persona' => 'natural'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '304',
                'concepto' => 'Servicios predomina el intelecto no relacionados con el título profesional',
                'porcentaje' => 8.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '304A',
                'concepto' => 'Comisiones y demás pagos por servicios predomina el intelecto no relacionados con el título profesional',
                'porcentaje' => 8.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '304B',
                'concepto' => 'Pagos a notarios y registradores de la propiedad y mercantil por sus actividades ejercidas como tales',
                'porcentaje' => 8.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '304C',
                'concepto' => 'Pagos a deportistas, entrenadores, árbitros, miembros del cuerpo técnico por sus actividades ejercidas como tales',
                'porcentaje' => 8.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '304D',
                'concepto' => 'Pagos a artistas por sus actividades ejercidas como tales',
                'porcentaje' => 8.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '304E',
                'concepto' => 'Honorarios y demás pagos por servicios de docencia',
                'porcentaje' => 8.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '307',
                'concepto' => 'Servicios predomina la mano de obra',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '308',
                'concepto' => 'Servicios entre sociedades',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '309',
                'concepto' => 'Servicios prestados por medios de comunicación y agencias de publicidad',
                'porcentaje' => 1.75,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '310',
                'concepto' => 'Servicio de transporte privado de pasajeros o transporte público o privado de carga',
                'porcentaje' => 1.75,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '311',
                'concepto' => 'Pagos a través de liquidaciones de compra (nivel cultural o rusticidad)',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '312',
                'concepto' => 'Transferencia de bienes muebles de naturaleza corporal',
                'porcentaje' => 1.75,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '312A',
                'concepto' => 'Compra de bienes de origen agrícola, avícola, pecuario, apícola, cunícula, bioacuático, y forestal',
                'porcentaje' => 1.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '314A',
                'concepto' => 'Regalías por concepto de franquicias de acuerdo a Ley de Propiedad Intelectual - pago a personas naturales',
                'porcentaje' => 8.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '314B',
                'concepto' => 'Cánones, derechos de autor, marcas, patentes y similares de acuerdo a Ley de Propiedad Intelectual – pago a personas naturales',
                'porcentaje' => 8.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '314C',
                'concepto' => 'Regalías por concepto de franquicias de acuerdo a Ley de Propiedad Intelectual - pago a sociedades',
                'porcentaje' => 8.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '314D',
                'concepto' => 'Cánones, derechos de autor, marcas, patentes y similares de acuerdo a Ley de Propiedad Intelectual – pago a sociedades',
                'porcentaje' => 8.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '319',
                'concepto' => 'Cuotas de arrendamiento mercantil (prestado por sociedades), inclusive la de opción de compra',
                'porcentaje' => 1.75,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '320',
                'concepto' => 'Arrendamiento bienes inmuebles',
                'porcentaje' => 8.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '322',
                'concepto' => 'Seguros y reaseguros (primas y cesiones)',
                'porcentaje' => 1.75,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323',
                'concepto' => 'Rendimientos financieros pagados a naturales y sociedades',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323A',
                'concepto' => 'Rendimientos financieros: depósitos Cta. Corriente',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323B1',
                'concepto' => 'Rendimientos financieros: depósitos Cta. Ahorros Sociedades',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323E',
                'concepto' => 'Rendimientos financieros: depósito a plazo fijo gravados',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323E2',
                'concepto' => 'Rendimientos financieros: depósito a plazo fijo exentos',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323F',
                'concepto' => 'Rendimientos financieros: operaciones de reporto - repos',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323G',
                'concepto' => 'Inversiones (captaciones) rendimientos distintos de aquellos pagados a IFIs',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323H',
                'concepto' => 'Rendimientos financieros: obligaciones',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323I',
                'concepto' => 'Rendimientos financieros: bonos convertible en acciones',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323 M',
                'concepto' => 'Rendimientos financieros: Inversiones en títulos valores en renta fija gravados ',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323 N',
                'concepto' => 'Rendimientos financieros: Inversiones en títulos valores en renta fija exentos',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323 O',
                'concepto' => 'Intereses y demás rendimientos financieros pagados a bancos y otras entidades sometidas al control de la Superintendencia de Bancos y de la Economía Popular y Solidaria',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323 P',
                'concepto' => 'Intereses pagados por entidades del sector público a favor de sujetos pasivos',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323Q',
                'concepto' => 'Otros intereses y rendimientos financieros gravados ',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '323R',
                'concepto' => 'Otros intereses y rendimientos financieros exentos',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '324A',
                'concepto' => 'Intereses y comisiones en operaciones de crédito entre instituciones del sistema financiero y entidades economía popular y solidaria.',
                'porcentaje' => 1.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '324B',
                'concepto' => 'Inversiones entre instituciones del sistema financiero y entidades economía popular y solidaria',
                'porcentaje' => 1.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '325',
                'concepto' => 'Anticipo dividendos',
                'porcentaje' => 22.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '325A',
                'concepto' => 'Préstamos accionistas, beneficiarios o partícipes',
                'porcentaje' => 22.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '326',
                'concepto' => 'Dividendos distribuidos que correspondan al impuesto a la renta único establecido en el art. 27 de la LRTI',
                'porcentaje' => 0.00,
                'categoria' => 'dividendos'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '327',
                'concepto' => 'Dividendos distribuidos a personas naturales residentes',
                'porcentaje' => 0.00,
                'categoria' => 'dividendos'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '328',
                'concepto' => 'Dividendos distribuidos a sociedades residentes',
                'porcentaje' => 0.00,
                'categoria' => 'dividendos'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '329',
                'concepto' => 'Dividendos distribuidos a fideicomisos residentes',
                'porcentaje' => 0.00,
                'categoria' => 'dividendos'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '330',
                'concepto' => 'Dividendos gravados distribuidos en acciones (reinversión de utilidades sin derecho a reducción tarifa IR)',
                'porcentaje' => 0.00,
                'categoria' => 'dividendos'
            ],

            // Continúa Códigos de IR
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '332',
                'concepto' => 'Otras compras de bienes y servicios no sujetas a retención',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '332B',
                'concepto' => 'Compra de bienes inmuebles',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '332C',
                'concepto' => 'Transporte público de pasajeros',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '332D',
                'concepto' => 'Pagos en el país por transporte de pasajeros o transporte internacional de carga, a compañías nacionales o extranjeras',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '332E',
                'concepto' => 'Valores entregados por las cooperativas de transporte a sus socios',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '332F',
                'concepto' => 'Compraventa de divisas distintas al dólar de los Estados Unidos de América',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '332G',
                'concepto' => 'Pagos con tarjeta de crédito',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '332H',
                'concepto' => 'Pago al exterior tarjeta de crédito reportada por la Emisora de tarjeta de crédito',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '333',
                'concepto' => 'Convenio de Débito o Recaudación',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '334',
                'concepto' => 'Por energía eléctrica',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '335',
                'concepto' => 'Por actividades de construcción de obra material inmueble, urbanización, lotización o actividades similares',
                'porcentaje' => 1.75,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '336',
                'concepto' => 'Compra de bienes muebles de naturaleza corporal a personas naturales no obligadas a llevar contabilidad',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '337',
                'concepto' => 'Compra de producción nacional de bienes que se exporten',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '338',
                'concepto' => 'Por rendimientos financieros pagados a naturales y sociedades  (NO A IFIs)',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '339',
                'concepto' => 'Por rendimientos financieros: depositados en cuentas de ahorro y corriente ',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '340',
                'concepto' => 'Otras retenciones aplicables el 1%',
                'porcentaje' => 1.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '341',
                'concepto' => 'Otras retenciones aplicables el 2%',
                'porcentaje' => 2.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '342',
                'concepto' => 'Otras retenciones aplicables el 8%',
                'porcentaje' => 8.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '343',
                'concepto' => 'Otras retenciones aplicables el 25%',
                'porcentaje' => 25.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '344',
                'concepto' => 'Otras retenciones aplicables a otros porcentajes',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '346',
                'concepto' => 'Honorarios profesionales y servicios desarrollados predominantemente por el intelecto - personas naturales donde se emite una liquidación de compra por servicios',
                'porcentaje' => 10.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IR',
                'codigo' => '349',
                'concepto' => 'Subrogación de deuda',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],

            // CÓDIGOS DE IVA
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '721',
                'concepto' => 'Retención del 30% - Transferencia de bienes gravados con tarifa diferente de 0% de IVA',
                'porcentaje' => 30.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '723',
                'concepto' => 'Retención del 70% - Por la prestación de servicios gravados con tarifa diferente de 0% de IVA',
                'porcentaje' => 70.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '725',
                'concepto' => 'Retención del 100% - Honorarios profesionales y demás pagos por servicios relacionados con el título profesional',
                'porcentaje' => 100.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '727',
                'concepto' => 'Retención del 100% - Arrendamiento de inmuebles a personas naturales',
                'porcentaje' => 100.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '729',
                'concepto' => 'Retención del 100% - Liquidaciones de compra de bienes o prestación de servicios',
                'porcentaje' => 100.00,
                'categoria' => 'normal'
            ],
            // Agregar estos al array de códigos en el seeder
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '731',
                'concepto' => 'Retención del 0% - Contribuyentes Especiales a Contribuyentes Especiales',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '733',
                'concepto' => 'Retención del 0% - Convenios de Débito o Recaudación',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '735',
                'concepto' => 'Retención del 0% - Compras a entidades del Sector Público',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '737',
                'concepto' => 'Retención del 0% - Compras a Exportadores Habituales de Bienes',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '739',
                'concepto' => 'Retención del 0% - Instituciones del Sistema Financiero y entidades de la Economía Popular y Solidaria',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '741',
                'concepto' => 'Retención del 0% - Pago con tarjeta de crédito o débito',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '743',
                'concepto' => 'Retención del 0% - Pago por servicio de transporte de pasajeros o transporte internacional de carga',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ],
            [
                'tipo_impuesto' => 'IV',
                'codigo' => '745',
                'concepto' => 'Retención del 0% - Pago por compra de bienes o servicios con tarifa 0% de IVA',
                'porcentaje' => 0.00,
                'categoria' => 'normal'
            ]
        ];

        $now = Carbon::now();

        foreach ($codigosIR as $codigo) {
            CodigoRetencion::create(array_merge($codigo, [
                'activo' => true,
                'fecha_inicio' => $now,
                'validaciones' => $this->getValidacionesPorCodigo($codigo['codigo'])
            ]));
        }
    }
    private function getValidacionesPorCodigo(string $codigo): ?array
    {
        $validaciones = [
            // Validaciones para códigos específicos
            '325' => [
                [
                    'campo' => 'detalles.*.base_imponible',
                    'operador' => '>',
                    'valor' => 0,
                    'mensaje' => 'La base imponible debe ser mayor a 0'
                ]
            ],
            '327' => [
                [
                    'campo' => 'detalles.*.base_imponible',
                    'operador' => '>',
                    'valor' => 0,
                    'mensaje' => 'La base imponible debe ser mayor a 0'
                ],
                [
                    'campo' => 'sujeto.tipo_sujeto',
                    'operador' => '=',
                    'valor' => 'natural',
                    'mensaje' => 'Este código solo aplica para personas naturales'
                ]
            ],
            '328' => [
                [
                    'campo' => 'sujeto.tipo_sujeto',
                    'operador' => '=',
                    'valor' => 'sociedad',
                    'mensaje' => 'Este código solo aplica para sociedades'
                ]
            ],
            '303' => [
                [
                    'campo' => 'sujeto.tipo_sujeto',
                    'operador' => '=',
                    'valor' => 'natural',
                    'mensaje' => 'Este código solo aplica para personas naturales'
                ]
            ],
            '304' => [
                [
                    'campo' => 'sujeto.tipo_sujeto',
                    'operador' => '=',
                    'valor' => 'natural',
                    'mensaje' => 'Este código solo aplica para personas naturales'
                ]
            ],
            '320' => [
                [
                    'campo' => 'detalles.*.base_imponible',
                    'operador' => '>',
                    'valor' => 0,
                    'mensaje' => 'La base imponible debe ser mayor a 0'
                ]
            ],
            '323' => [
                [
                    'campo' => 'detalles.*.base_imponible',
                    'operador' => '>',
                    'valor' => 0,
                    'mensaje' => 'La base imponible debe ser mayor a 0'
                ]
            ],
            // Validaciones para códigos de IVA
            '721' => [
                [
                    'campo' => 'detalles.*.base_imponible',
                    'operador' => '>',
                    'valor' => 0,
                    'mensaje' => 'La base imponible debe ser mayor a 0'
                ]
            ],
            '723' => [
                [
                    'campo' => 'detalles.*.base_imponible',
                    'operador' => '>',
                    'valor' => 0,
                    'mensaje' => 'La base imponible debe ser mayor a 0'
                ]
            ],
            '725' => [
                [
                    'campo' => 'detalles.*.base_imponible',
                    'operador' => '>',
                    'valor' => 0,
                    'mensaje' => 'La base imponible debe ser mayor a 0'
                ]
            ]
        ];

        return $validaciones[$codigo] ?? null;
    }
}
