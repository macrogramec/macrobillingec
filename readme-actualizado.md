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
