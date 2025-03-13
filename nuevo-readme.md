# Sistema de Facturación Electrónica del Ecuador a Gran Escala

**Desarrollado por MACROGRAM CIA LTDA.**  
**Ubicación: Guayaquil - Daule - Ecuador**

## 1. Requerimientos del Proyecto

### 1.1 Requisitos Técnicos
- PHP >= 8.2
- MySQL 8.0 con soporte UTF8MB4
- Composer 2.x
- Laravel 11.x
- Node.js (opcional para recursos front-end)

### 1.2 Características Principales
- **Escalabilidad**: Diseñado para soportar más de 1,000,000 de facturas
- **Autenticación**: OAuth2 con manejo de roles y permisos
- **Multiambiente**: Gestión separada de entornos de desarrollo y producción
- **Validación en Tiempo Real**: Verificación inmediata con el SRI
- **Base de Datos**: MySQL RDS en AWS
- **Formato de Entrada**: JSON para todas las operaciones
- **Control de Versiones**: Soporte para versiones 1.0.0 hasta 2.1.0 del SRI

## 2. Estructura Actual del Proyecto

### 2.1 Componentes Principales

#### Controllers
- `AdminController`: Gestión de usuarios y administración
- `AuthController`: Autenticación y manejo de tokens
- `EmpresaController`: CRUD de empresas
- `EstablecimientoController`: CRUD de establecimientos
- `PuntoEmisionController`: CRUD de puntos de emisión
- `FacturacionController`: Gestión de facturación electrónica
- `SRIController`: Integración con servicios del SRI

#### Services
- `AuthService`: Servicios de autenticación
- `EmpresaService`: Lógica de negocio para empresas
- `FacturacionService`: Procesamiento de facturas
- `SRIService`: Comunicación con el SRI
- `CalculadorImpuestosService`: Cálculo de impuestos
- `ValidadorImpuestosService`: Validación de impuestos
- `ClaveAccesoGenerator`: Generación de claves de acceso

### 2.2 Estructuras de Datos Principales
- Empresas
- Establecimientos
- Puntos de Emisión
- Facturas
- Impuestos
- Formas de Pago

## 3. Procesos en Base de Datos

### 3.1 Tablas Principales
- `empresas`: Información de contribuyentes
- `establecimientos`: Sucursales
- `puntos_emision`: Puntos de facturación
- `facturas`: Documentos electrónicos
- `factura_detalles`: Detalles de facturas
- `factura_impuestos`: Impuestos aplicados
- `factura_estados`: Estados y trazabilidad

### 3.2 Tablas de Configuración
- `tipos_impuestos`: Catálogo de impuestos
- `tarifas_impuestos`: Tarifas y porcentajes
- `formas_pago`: Métodos de pago disponibles
- `condiciones_impuestos`: Reglas de aplicación

## 4. Procesos de Consulta y Validación

### 4.1 Validaciones Principales
- Validación de RUC/Cédula
- Validación de montos y cálculos
- Validación de impuestos según tipo de producto
- Validación de formas de pago
- Validación de secuenciales

### 4.2 Consultas Optimizadas
- Índices para búsqueda por clave de acceso
- Índices compuestos para relaciones
- Control de estados y fechas
- Búsqueda por establecimiento y punto de emisión

## 5. Procesos Generales

### 5.1 Facturación
1. Validación de datos de entrada
2. Cálculo de impuestos
3. Generación de clave de acceso
4. Firma electrónica
5. Envío al SRI
6. Autorización
7. Generación de PDF/XML

### 5.2 Seguridad
- OAuth2 para autenticación
- Scopes por tipo de usuario:
  - admin
  - user
  - desarrollo
  - produccion
- Rate Limiting:
  - Admin: 2000 req/min
  - Desarrollo: 1000 req/min
  - Producción: 1500 req/min
  - Usuario base: 500 req/min

### 5.3 Manejo de Errores
- Log detallado de errores
- Trazabilidad completa
- Reintentos automáticos
- Notificaciones por correo

## 6. Características Adicionales

### 6.1 Control de Ambientes
- Ambiente de pruebas
- Ambiente de producción
- Gestión separada de claves
- Validación específica por ambiente

### 6.2 Multiempresa
- Soporte para múltiples contribuyentes
- Separación de establecimientos
- Control de puntos de emisión
- Manejo independiente de secuenciales

### 6.3 Gestión de Versiones
- Soporte para todas las versiones del SRI
- Migración automática entre versiones
- Validaciones específicas por versión
- Control de cambios y auditoría

### 6.4 API REST
- Documentación Swagger
- Headers informativos
- CORS configurado
- Versionamiento de endpoints

## Instalación y Configuración

```bash
# Clonar repositorio
git clone [URL_REPOSITORIO]
cd macrobillingec

# Instalar dependencias
composer install

# Configurar variables de entorno
cp .env.example .env
php artisan key:generate

# Migrar base de datos
php artisan migrate --seed

# Instalar Passport
php artisan passport:install

# Iniciar servidor
php artisan serve
```

## Endpoints Principales

### Autenticación
- **POST** `/api/oauth/token`: Obtener token de acceso
- **POST** `/api/create-first-admin`: Crear primer administrador

### Empresas
- **GET/POST** `/api/empresas`: Listar/Crear empresas
- **GET/PUT/DELETE** `/api/empresas/{id}`: Obtener/Actualizar/Eliminar empresa

### Facturación
- **POST** `/api/facturacion`: Crear factura
- **GET** `/api/facturacion/{claveAcceso}`: Consultar factura
- **POST** `/api/facturacion/{claveAcceso}/anular`: Anular factura
- **GET** `/api/facturacion/{claveAcceso}/pdf`: Descargar PDF
- **GET** `/api/facturacion/{claveAcceso}/xml`: Descargar XML

## Soporte y Contacto

- **Empresa**: MACROGRAM CIA LTDA.
- **Ubicación**: Guayaquil - Daule - Ecuador
- **Email**: soporte@macrobilling.com
- **Teléfono**: +593 4 123 4567

---

**© 2024 MACROGRAM CIA LTDA. Todos los derechos reservados.**
