# Migraciones de experiencias

Antes de desplegar una versión que ya no ejecuta cambios de esquema durante las solicitudes, crea un respaldo completo y ejecuta la migración desde la raíz del proyecto:

```powershell
C:\xampp\php\php.exe .\database\backup-and-migrate.php --backup=C:\secure-backups\hotel-before-experiences.sql
```

Si el puerto de la instancia no coincide con `DB_PORT`, indícalo explícitamente; nunca se elige otro puerto automáticamente:

```powershell
C:\xampp\php\php.exe .\database\backup-and-migrate.php --port=3306 --backup=C:\secure-backups\hotel-before-experiences.sql
```

El respaldo se completa antes de modificar el esquema. Si `mysqldump` falla, la migración no se ejecuta. El respaldo no se sobrescribe si ya existe. El migrador es idempotente y requiere que las tablas base (`reservas` y `usuario`) existan para completar la asociación histórica de actividades con reservas.

Verifica el mensaje de éxito y conserva el respaldo en una ubicación protegida antes de publicar la nueva versión. No ejecutes estas herramientas por HTTP. Para ejecutar únicamente una migración previamente respaldada, usa `database/migrate-experiences.php`; admite `--port=PUERTO` explícito.
