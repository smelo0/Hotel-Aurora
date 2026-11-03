-- Unifica los roles de personal en el rol 3 (Empleado).
-- Ejecutar en bases existentes; puede repetirse para aplicar la desactivación de otras cuentas.
START TRANSACTION;

INSERT IGNORE INTO rol_permiso (cod_rol, cod_permiso)
SELECT 3, rp.cod_permiso
FROM rol_permiso rp
WHERE rp.cod_rol NOT IN (1, 3, 6);

UPDATE usuario
SET cod_rol_usu = 3
WHERE cod_rol_usu NOT IN (1, 6);

UPDATE usuario
SET est_usu = 0
WHERE cod_rol_usu = 3
  AND (corr_usu IS NULL OR LOWER(TRIM(corr_usu)) <> 'yulli@hotel.com');

UPDATE rol
SET des_rol = 'Empleado',
    detalle_rol = 'Perfil del personal del hotel.'
WHERE cod_rol = 3;

UPDATE rol
SET des_rol = 'Gerente General'
WHERE cod_rol = 1;

UPDATE rol
SET des_rol = 'Cliente'
WHERE cod_rol = 6;

DELETE FROM rol
WHERE cod_rol NOT IN (1, 3, 6);

COMMIT;
