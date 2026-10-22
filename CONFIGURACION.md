# Configuración local

1. Copia `.env.example` como `.env`.
2. Configura `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_PORT` y `DB_TIMEOUT`.
3. Importa `SQL/hotel.sql` en la base configurada.
4. Inicia Apache y MySQL.

El archivo `.env` es opcional cuando Apache entrega las mismas variables de
entorno. En producción usa HTTPS, `APP_ENV=production` y
`SESSION_COOKIE_SECURE=true`.

No subas `.env`, respaldos ni dumps de producción. Las credenciales incluidas
en el dump son únicamente para demostración local.

## Docker (sin XAMPP)

Con Docker Desktop instalado:

1. Ejecuta `docker compose up --build -d`.
2. Ejecuta `docker compose exec web php database/migrate.php`.
3. Abre `http://localhost:8080`.

La base de pruebas queda en el volumen `mysql-data`. Para borrarla por
completo, usa `docker compose down -v`; esto elimina todas las reservas y
pagos de ese entorno local.
