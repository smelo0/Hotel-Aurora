# Hotel Aurora - Diseño del Proyecto

## Visión General
Sistema de gestión hotelera completo desarrollado en PHP 8.3.3 con arquitectura modular y patrón MVC. El sistema gestiona todas las operaciones hoteleras incluyendo reservas, habitaciones, usuarios, empleados, experiencias y servicios de room service.

## Arquitectura del Sistema

### Estructura de Carpetas
```
/src                  # Código fuente principal
  /Admin              # Módulo de administración
  /Billing            # Módulo de facturación/pagos
  /Database           # Capa de acceso a datos
  /Empleado           # Módulo de empleados
  /Experiencia        # Módulo de experiencias/actividades
  /Reserva            # Módulo de reservas
  /RoomService        # Módulo de servicios de habitaciones
  /Usuario            # Módulo de gestión de usuarios

/controladores        # Controladores PHP que manejan la lógica
/interfaz             # Vistas/frontends por rol de usuario
  /admin              # Interfaz administrativa
  /empleado           # Interfaz operativa para empleados
  /institucional      # Páginas institucionales
  /legal              # Páginas legales
  /loggins            # Sistema de login/acceso
  /usuario            # Interfaz para huéspedes

/assets               # Recursos estáticos (CSS, JS, imágenes)
/database             # Migraciones y scripts de base de datos
/configuracion        # Archivos de configuración
```

### Patrón de Diseño
- **MVC (Model-View-Controller):** Los controladores en `controladores/` gestionan la lógica, las vistas en `interfaz/` manejan la presentación.
- **Repositorio Pattern:** Acceso a datos a través de clases en `src/*/` (ej. `EmpleadoRepository.php`).
- **Service Layer:** Regla de negocio en `src/*/Service.php` (ej. `EmpleadoService.php`).

## Diseño de Base de Datos

### Tablas Principales
- **users:** Usuarios del sistema (empleados, huéspedes, administradores)
- **rooms:** Habitaciones del hotel (estado, tipo, precio)
- **reservas:** Reservas realizadas (fecha check-in/out, estado, usuario)
- **experiencias:** Actividades y servicios adicionales
- **empleados:** Información del personal hotelero
- **tareas:** Tareas asignadas a empleados
- **pagos:** Registros de pagos (integración Wompi)

### Relaciones Clave
- Un **usuario** puede tener múltiples **reservas**
- Una **reserva** pertenece a un **usuario** y puede tener múltiples **pagos**
- Una **habitación** puede tener múltiples **reservas** a lo largo del tiempo
- **Empleados** pueden tener **tareas** asignadas
- **Experiencias** están vinculadas a **reservas** o disponibilidad

## Módulos del Sistema

### 1. Módulo de Usuarios (`/src/Usuario`)
- Registro y autenticación de huéspedes
- Recuperación de contraseña
- Perfil de usuario
- Autenticación Google OAuth

### 2. Módulo de Reservas (`/src/Reserva`)
- Creación y gestión de reservas
- Consulta de disponibilidad
- Modificación de reservas
- Cancelaciones

### 3. Módulo de Habitaciones (`/src/RoomService` y `/controladores/gestionar_habitaciones.php`)
- Catálogo de habitaciones
- Estado de habitaciones (limpia, ocupada, mantenimiento)
- Asignación de habitaciones a reservas

### 4. Módulo de Empleados (`/src/Empleado` y `/interfaz/empleado`)
- Panel de control para empleados
- Gestión de tareas
- Consulta de habitaciones y reservas
- Cierre de sesión con token CSRF

### 5. Módulo de Experiencias (`/src/Experiencia` y `/controladores/gestionar_experiencias.php`)
- Actividades adicionales para huéspedes
- Reservas de experiencias
- Catálogo de servicios

### 6. Módulo de Billing/Pagos (`/src/Billing` y `/controladores/`)
- Procesamiento de pagos con Wompi
- Historial de transacciones
- Facturación

### 7. Módulo de Administración (`/src/Admin` y `/interfaz/admin`)
- Panel de control completo
- Gestión de usuarios
- Configuración del sistema
- Reportes

## Interfaz de Usuario

### Roles y Vistas
- **Huésped:** Formularios de registro, reserva, perfil, restablecer contraseña
- **Empleado:** Panel operativo en `interfaz/empleado` - habitaciones, tareas, consultas
- **Administrador:** Panel completo en `interfaz/admin` - gestión total

### Flujo de Páginas
1. **Login/Acceso:** `validar_usuario.php`, `validar_acceso.php`
2. **Registro:** `registrar_usuario.php` (requiere aceptación de tratamiento de datos, contraseña mínima 12 caracteres)
3. **Reserva:** Flujo completo desde selección de habitación hasta confirmación
4. **Perfil:** Configuración del usuario, contraseña, datos personales
5. **Administración:** Solo para roles de admin

## Puntos de Integración API

### Endpoints JSON
- **Habitaciones:** `/obtener_habitaciones.php` - Consultas disponibles
- **Tareas:** `/obtener_tareas.php` - Tareas asignadas por sesión
- **Estado:** `/obtener_estado_habitacion.php` - Estado actual de habitaciones

### Características API
- Mantener compatibilidad con paneles de empleado y administrador
- Respuestas en formato JSON
- Endpoints aislados en `src/*/Repository.php`

## Seguridad

### Autenticación
- **reCAPTCHA v2:** Formularios de registro y contacto (obligatorio en producción)
- **Google OAuth:** Cuentas de huéspedes registradas
- **SMTP/Gmail:** Correos transaccionales (recuperación de contraseña)
- **CSRF Token:** Cierre de sesión empleado (POST method)

### Validaciones
- Contraseña mínima 12 caracteres, máximo 72 bytes (límite bcrypt)
- Formatos de email válidos
- Validación de lado servidor (no confiar en identificadores enviados por navegador)
- Autoría de tareas determinada en servidor

## Configuración de Entorno

### Variables de Entorno (.env)
```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=hotel
DB_PORT=3306
APP_BASE_URL=http://localhost/Hotel-Aurora
SESSION_COOKIE_SECURE=false
WOMPI_PRIVATE_KEY=your-wompi-private-key
SMTP_HOST=smtp.gmail.com
SMTP_USER=your-smtp-user
SMTP_PASS=your-smtp-password
SMTP_PORT=587
FROM_EMAIL=your-sender@example.com
FROM_NAME="Hotel Aurora"
GOOGLE_CLIENT_ID=

RECAPTCHA_SITE_KEY=pega_aqui_la_clave_publica
RECAPTCHA_SECRET_KEY=pega_aqui_la_clave_secreta
```

### Configuración de Servidor
- **PHP:** 8.3.3
- **Apache:** XAMPP
- **Base de datos:** MySQL/MariaDB puerto 3306
- **Dominios reCAPTCHA:** localhost y 127.0.0.1

## Guías de Desarrollo

### Flujos de Trabajo
1. **Nueva funcionalidad:** Crear rama `feature/...`, implementar en módulo correspondiente
2. **Modificar endpoints:** Mantener rutas y formatos JSON existentes
3. **Vistas operativas:** Permanecer en `interfaz/empleado`
4. **Base de datos:** Hacer respaldo antes de migraciones

### Convenios de Código
- Seguir patrones existentes en controladores
- Usar repositorios para acceso a datos (`src/*/Repository.php`)
- Regla de negocio en service layers (`src/*/Service.php`)
- Vistas en `interfaz/` según rol de usuario
- CSS/JS desde `assets/` en lugar de inline

### Limitaciones Conocidas
- No implementa transiciones de check-in/check-out en la interfaz
- El selector de idioma carga recursos desde `assets/`
- Cierre sesión empleado usa POST con token CSRF
- Endpoints GET de huésped y administración aceptados durante migración