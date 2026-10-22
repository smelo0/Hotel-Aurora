# Seguridad y datos de demostración

El archivo `SQL/hotel.sql` contiene datos de demostración exclusivamente.
No debe usarse para respaldos ni para cargar información de huéspedes reales.

Los dumps de producción deben guardarse fuera del repositorio, cifrados y con
acceso limitado. Usa el patrón `SQL/*.local.sql` para un dump local que no se
versionará.

Si este repositorio se compartió antes de esta limpieza:

1. Cambia las contraseñas de las cuentas incluidas en los dumps históricos.
2. Revoca y reemplaza cualquier secreto que se haya guardado fuera de `.env`.
3. Revisa el historial remoto; borrar el archivo actual no elimina copias ya
   publicadas.

Las credenciales de demostración de `SQL/hotel.sql` son públicas por diseño:
no deben reutilizarse en ningún entorno real.
