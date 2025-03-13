# Sistema de Facturación Electrónica del Ecuador a Gran Escala

**Desarrollado por MACROGRAM CIA LTDA.**  
**Ubicación: Guayaquil - Daule - Ecuador**

---

## Descripción del Proyecto

Este sistema es una **API REST FULL** desarrollada en **Laravel 11** para la gestión de facturación electrónica en el Ecuador. Diseñado para manejar grandes volúmenes de información, el sistema soporta más de 1,000,000 de facturas, con validaciones en tiempo real y conexión directa con el Servicio de Rentas Internas (SRI).

### Características Principales
- **Escalabilidad**: Diseñado para soportar grandes volúmenes de datos y múltiples empresas.
- **Autenticación Segura**: Implementación de OAuth2 para el manejo de credenciales y accesos seguros.
- **Ambientes Separados**: Gestión de claves para desarrollo y producción.
- **Validación en Tiempo Real**: Verificación inmediata de los datos antes de su envío al SRI.
- **Entrada JSON**: Toda la información debe ser enviada en formato JSON.
- **Base de Datos MySQL RDS**: Solución en la nube para un almacenamiento seguro y escalable.
- **Multiversion SRI**: Soporte para todas las versiones de documentos electrónicos del SRI (1.0.0 hasta 2.1.0)

## Estructura de Base de Datos

### Tablas Principales

1. **facturas**
   - Almacena información principal de las facturas
   - Soporte multi-versión (1.0.0 hasta 2.1.0)
   - Control de estados y respuestas del SRI
   - Campos para comercio exterior y régimen tributario
   - Codificación UTF-8MB4 para caracteres especiales

2. **factura_detalles**
   - Detalles de productos/servicios
   - Manejo de impuestos (IVA, ICE, IRBPNR)
   - Soporte para subsidios y descuentos
   - Control de versiones por detalle

3. **factura_estados**
   - Seguimiento detallado del ciclo de vida del documento
   - Control de autorizaciones SRI
   - Manejo de errores y contingencias
   - Historial de cambios
   - Sistema de reintentos automáticos

4. **formas_pago**
   - Catálogo de formas de pago según SRI
   - Soporte multi-versión
   - Control de requerimientos (plazos/bancos)
   - Manejo de estados activo/inactivo

5. **factura_detalles_adicionales**
   - Información adicional clave-valor
   - Control de orden en XML
   - Soporte para múltiples versiones
   - Validación de longitudes según SRI

### Características Técnicas

#### Codificación y Caracteres Especiales
```sql
-- Configuración de Base de Datos
ALTER DATABASE macrobillingec CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### Índices Optimizados
- Índices para búsqueda por clave de acceso
- Índices para estados y fechas
- Índices compuestos para consultas frecuentes

#### Control de Versiones
- Soporte para todas las versiones del SRI
- Migración automática entre versiones
- Validación específica por versión

## Esquemas XSD

Implementación de esquemas XSD para validación de documentos:
- Factura v1.0.0 (Implementado)
- Soporte para validación en VB.NET
- Patrones de validación según SRI
- Control de tipos de datos y restricciones

## Instalación y Configuración

### Requisitos del Sistema
- PHP >= 8.2
- MySQL 8.0 con soporte UTF8MB4
- Composer 2.x
- Laravel 11.x

### Pasos de Instalación

1. Clonar el repositorio:
```bash
git clone [URL_REPOSITORIO]
cd macrobillingec
```

2. Instalar dependencias:
```bash
composer install
```

3. Configurar el archivo .env:
```env
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

4. Ejecutar migraciones y seeders:
```bash
php artisan migrate
php artisan db:seed
```

### Configuración de la Base de Datos

1. Crear la base de datos:
```sql
CREATE DATABASE macrobillingec CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Ejecutar migraciones específicas:
```bash
php artisan migrate --path=/database/migrations/[fecha]_create_facturas_table.php
php artisan migrate --path=/database/migrations/[fecha]_create_factura_detalles_table.php
php artisan migrate --path=/database/migrations/[fecha]_create_factura_estados_table.php
php artisan migrate --path=/database/migrations/[fecha]_create_formas_pago_table.php
php artisan migrate --path=/database/migrations/[fecha]_create_factura_detalles_adicionales_table.php
```

3. Cargar datos iniciales:
```bash
php artisan db:seed --class=FormasPagoSeeder
```

## Control de Cambios

### Versión 1.0.0 (21/11/2024)
- Implementación inicial del sistema
- Creación de estructura base de datos
- Soporte multi-versión documentos SRI
- Implementación de formas de pago
- Esquema XSD para validación VB.NET

### Cambios Pendientes
- Implementación de Swagger UI
- Sistema de notificaciones
- Panel de administración
- Reportes y estadísticas

## Soporte y Contacto

- **Empresa**: MACROGRAM CIA LTDA.
- **Ubicación**: Guayaquil - Daule - Ecuador
- **Email**: soporte@macrobilling.com
- **Teléfono**: +593 4 123 4567

---

**© 2024 MACROGRAM CIA LTDA. Todos los derechos reservados.**


Sí, aquí está el texto formateado para el Project Knowledge. Deberíamos empezar con:

# Sistema de Facturación Electrónica - Project Knowledge

<info>
Este documento contiene la información y conocimiento acumulado del proyecto de Sistema de Facturación Electrónica para Ecuador, incluyendo sus módulos, componentes y consideraciones técnicas.
</info>

## 1. Estructura del Sistema

### 1.1 Módulos Principales

#### Módulo de Gestión Empresarial
El sistema está estructurado en tres niveles jerárquicos principales:

1. **Empresas**
    - Entidad principal que contiene la información fiscal
    - Maneja datos de contribuyente
    - Controla ambientes globales

2. **Establecimientos**
    - Sucursales o puntos de operación
    - Dependientes de una empresa
    - Manejan configuraciones independientes

3. **Puntos de Emisión**
    - Puntos específicos de facturación
    - Control de secuenciales
    - Asociados a establecimientos

### 1.2 Arquitectura REST

#### Estructura de Endpoints
La API sigue una estructura jerárquica de recursos:

```
/api/empresas
    /{empresa_id}/establecimientos
        /{establecimiento_id}/puntos-emision
            /{punto_emision_id}/secuencial
```

#### Documentación Swagger
- Disponible en `/api/documentation`
- Incluye todos los endpoints
- Muestra esquemas de datos
- Proporciona ejemplos de uso

## 2. Reglas de Negocio

### 2.1 Validaciones de Empresa

- **RUC**:
    - Debe tener 13 dígitos
    - Debe ser válido según algoritmo módulo 11
    - Debe ser único en el sistema

- **Información Fiscal**:
  ```json
  {
    "ruc": "0992877878001",
    "razon_social": "EMPRESA EJEMPLO S.A.",
    "obligado_contabilidad": true,
    "contribuyente_especial": "12345",
    "ambiente": "produccion|pruebas",
    "tipo_emision": "normal|contingencia"
  }
  ```

### 2.2 Validaciones de Establecimiento

- **Código**:
    - 3 dígitos numéricos
    - Único por empresa
    - Formato: "001", "002", etc.

- **Estados**:
  ```json
  {
    "estado": "activo|inactivo",
    "ambiente": "produccion|pruebas"
  }
  ```

### 2.3 Validaciones de Punto de Emisión

- **Código**:
    - 3 dígitos numéricos
    - Único por establecimiento

- **Tipos de Comprobante**:
  ```json
  {
    "tipo_comprobante": {
      "01": "Factura",
      "02": "Nota de Débito",
      "03": "Nota de Crédito",
      "04": "Guía de Remisión",
      "05": "Comprobante de Retención",
      "06": "Nota de Crédito",
      "07": "Comprobante Complementario"
    }
  }
  ```

## 3. Implementación Técnica

### 3.1 Estructura de Base de Datos
```sql
-- Empresas
CREATE TABLE empresas (
    id BIGINT PRIMARY KEY,
    ruc VARCHAR(13) UNIQUE,
    razon_social VARCHAR(300),
    ambiente ENUM('produccion', 'pruebas'),
    -- otros campos fiscales
);

-- Establecimientos
CREATE TABLE establecimientos (
    id BIGINT PRIMARY KEY,
    empresa_id BIGINT,
    codigo CHAR(3),
    estado ENUM('activo', 'inactivo'),
    UNIQUE(empresa_id, codigo)
);

-- Puntos de Emisión
CREATE TABLE puntos_emision (
    id BIGINT PRIMARY KEY,
    establecimiento_id BIGINT,
    codigo CHAR(3),
    tipo_comprobante CHAR(2),
    secuencial_actual BIGINT,
    UNIQUE(establecimiento_id, codigo, tipo_comprobante)
);
```

### 3.2 Ejemplos de Uso

#### Crear Nueva Empresa
```bash
curl -X POST http://api.ejemplo.com/api/empresas \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "ruc": "0992877878001",
    "razon_social": "EMPRESA EJEMPLO S.A.",
    "ambiente": "produccion"
  }'
```

#### Añadir Establecimiento
```bash
curl -X POST http://api.ejemplo.com/api/empresas/1/establecimientos \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "codigo": "001",
    "direccion": "Guayaquil - Ecuador",
    "estado": "activo"
  }'
```

## 4. Consideraciones de Seguridad

### 4.1 Autenticación
- OAuth2 implementado
- Tokens con tiempo de expiración
- Scopes por tipo de operación

### 4.2 Validaciones
- Sanitización de entradas
- Validación de RUC
- Control de secuenciales
- Verificación de dependencias

## 5. Próximos Desarrollos

- [ ] Implementación de firma electrónica
- [ ] Conexión con servicios del SRI
- [ ] Sistema de notificaciones
- [ ] Reportes y estadísticas

## 6. Contacto y Soporte

**MACROGRAM CIA LTDA.**
- Ubicación: Guayaquil - Daule - Ecuador
- Email: soporte@macrobilling.com
- Teléfono: +593 4 123 4567

---

<note>
Este documento debe ser actualizado conforme se implementen nuevas características o se realicen cambios significativos en el sistema.
</note>

¿Necesitas que profundicemos en alguna sección específica o que agreguemos información adicional?
