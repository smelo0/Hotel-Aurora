# Hotel Aurora - Reservas múltiples por tipo

## Qué se cambió

1. El cliente puede seleccionar un tipo de habitación.
2. El cliente puede seleccionar la cantidad de habitaciones que necesita.
3. Todas las habitaciones se guardan dentro de una sola reserva (`reservas.cod_res`).
4. Cada habitación asignada se guarda como una fila independiente en `detalle`.
5. El servidor vuelve a comprobar disponibilidad antes de guardar.
6. El total se calcula multiplicando el precio de todas las habitaciones por las noches + IVA.
7. El administrador ve en una sola fila la reserva y todas sus habitaciones.
8. El empleado ve cada habitación reservada en su panel, con huésped, reserva, fechas y el indicador "Preparar habitación".
9. Las reservas antiguas de una sola habitación siguen siendo compatibles.
10. El historial del huésped agrupa las habitaciones de una misma reserva.

## Base de datos

No necesitas modificar las tablas `reservas` ni `detalle`: tu diseño actual ya permite la relación 1 reserva -> muchas habitaciones.

Puedes ejecutar opcionalmente:
`actualizacion_reservas_multiples.sql`

Ese archivo crea una vista de consulta llamada `vista_reservas_habitaciones`.

## Instalación

1. Haz una copia de seguridad de tu carpeta actual `Hotel-Aurora`.
2. Reemplaza los archivos del proyecto por los incluidos en este paquete.
3. Mantén tu archivo `.env` actual.
4. Mantén tu configuración de Wompi/reCAPTCHA.
5. Si quieres la vista SQL, importa `actualizacion_reservas_multiples.sql` en la misma base de datos.
6. Inicia Apache y MySQL desde XAMPP.
7. Prueba:
   - Cliente -> seleccionar fechas.
   - Elegir un tipo de habitación.
   - Seleccionar 2 o más habitaciones.
   - Confirmar/pagar.
   - Administrador -> Reservas.
   - Empleado -> Habitaciones.

## Ejemplo de resultado

Si el cliente selecciona:
- Tipo: Doble
- Cantidad: 3
- Fechas: 10 al 13

El sistema puede asignar automáticamente las habitaciones 201, 202 y 203 y crear:

reservas
  cod_res = 120

detalle
  cod_res_det = 120 | cod_hab_det = 201
  cod_res_det = 120 | cod_hab_det = 202
  cod_res_det = 120 | cod_hab_det = 203

Es decir: una sola reserva, tres habitaciones.