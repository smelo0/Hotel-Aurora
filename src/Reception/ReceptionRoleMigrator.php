<?php
declare(strict_types=1);

namespace App\Reception;

use mysqli;

/**
 * Registra (una sola vez) el rol "Recepcionista" (cod_rol 7) con sus permisos básicos.
 *
 * Es idempotente: solo inserta los permisos por defecto cuando el rol se crea, así que
 * lo que el gerente cambie después en "Roles y Permisos" no se pisa.
 * El mismo cambio está en SQL/recepcionista.sql por si prefieres importarlo a mano.
 */
final class ReceptionRoleMigrator
{
    public const COD_ROL = 7;

    /** Lo que necesita un recepcionista y nada más: sin finanzas, roles, logs, catálogos ni borrar reservas. */
    private const PERMISOS_POR_DEFECTO = [
        'dashboard.ver',
        'reservas.ver',
        'reservas.crear',
        'reservas.editar',
        'operaciones.ver',
        'operaciones.editar',
        'roomservice.estado',
    ];

    private static bool $verificado = false;

    public static function asegurar(mysqli $connection): void
    {
        if (self::$verificado) {
            return;
        }
        self::$verificado = true;

        try {
            $codRol = self::COD_ROL;
            $consulta = $connection->prepare('SELECT 1 FROM rol WHERE cod_rol = ? LIMIT 1');
            $consulta->bind_param('i', $codRol);
            $consulta->execute();
            $consulta->store_result();
            $existe = $consulta->num_rows > 0;
            $consulta->close();
            if ($existe) {
                return;
            }

            $nombre = 'Recepcionista';
            $detalle = 'Atención en recepción: reservas, check-in y check-out, cobro de alojamiento y estado de habitaciones.';
            $insertarRol = $connection->prepare('INSERT INTO rol (cod_rol, des_rol, detalle_rol) VALUES (?, ?, ?)');
            $insertarRol->bind_param('iss', $codRol, $nombre, $detalle);
            $insertarRol->execute();
            $insertarRol->close();

            $insertarPermiso = $connection->prepare(
                'INSERT IGNORE INTO rol_permiso (cod_rol, cod_permiso) SELECT ?, cod_permiso FROM permiso WHERE cod_permiso = ?'
            );
            foreach (self::PERMISOS_POR_DEFECTO as $permiso) {
                $insertarPermiso->bind_param('is', $codRol, $permiso);
                $insertarPermiso->execute();
            }
            $insertarPermiso->close();
        } catch (\Throwable $error) {
            error_log('No se pudo registrar el rol Recepcionista: ' . $error->getMessage());
        }
    }
}
