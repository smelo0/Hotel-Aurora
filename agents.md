# Hotel Aurora - Guía del Agente de IA

## Información del Proyecto
**Hotel Aurora** es un sistema de gestión hotelera desarrollado en PHP que gestiona reservas, habitaciones, experiencias, empleados, usuarios y servicios de room service.

## Tecnologías Utilizadas
- **Lenguaje:** PHP 8.3.3
- **Base de datos:** MySQL/MariaDB
- **Dependencias:** Composer
- **Frontend:** CSS, JavaScript
- **Autenticación:** Google OAuth, reCAPTCHA v2, SMTP/Gmail
- **Pagos:** Wompi (pasarela de pagos)

## Arquitectura del Sistema
- **Modular:** El proyecto está organizado en módulos bajo `src/`: Usuario, Empleado, Admin, Reserva, Experiencia, Billing, RoomService
- **Patrón MVC:** Controladores en `controladores/` y vistas en `interfaz/`
- **API REST:** Endpoints JSON para habitaciones y tareas
- **Base de datos:** Migraciones en `database/` con tablas para usuarios, habitaciones, reservas, experiencias

## Roles y Permisos
- **Huésped:** Registro, reservas, perfil, restablecer contraseña
- **Empleado:** Gestión de habitaciones, tareas, experiencias (vistas operativas en `interfaz/empleado`)
- **Administrador:** Gestión completa (habitaciones, experiencias, usuarios, reservas)

## Puntos de Integración Clave
- **reCAPTCHA:** Requerido para formularios de registro y contacto (claves en `.env`)
- **Google OAuth:** Autenticación de huéspedes (`GOOGLE_CLIENT_ID` en `.env`)
- **Wompi:** Procesamiento de pagos de reservas (clave privada en `.env`)
- **SMTP:** Envío de correos transaccionales (configuración en `.env`)

## Restricciones del Agente
1. Consulta todas las dudas necesarias antes de implementar cambios
2. **Se requiere permiso explícito** antes de modificar o eliminar archivos del proyecto
3. Mantener compatibilidad con endpoints JSON existentes para paneles de empleado y administrador
4. No implementar transiciones de check-in/check-out en la interfaz (solo estado disponible)
5. La autoría de tareas se determina en servidor, no desde identificadores enviados por el cliente
6. El selector de idioma carga CSS/JS desde `assets/` en lugar de estilos inline

## Guía de Trabajo
- Verificar variables de entorno en `.env` antes de hacer cambios relacionados con DB, pagos o autenticación
- Probar cambios en entorno local (XAMPP con PHP 8.3.3) antes de deploy
- Mantener respaldo de base de datos antes de migraciones
- Seguir patrones existentes en controladores y repositorios
- Las vistas operativas permanecen en `interfaz/empleado`