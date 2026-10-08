<?php
declare(strict_types=1);

namespace App\Empleado;

use mysqli;
use RuntimeException;

final class EmpleadoRepository
{
    public function fetchHabitaciones(mysqli $conexion): array
    {
        $sql = "SELECT h.cod_hab, h.num_hab, h.tipo_hab, h.pre_hab, h.est_hab, h.obs_hab,
                       r.cod_res, r.est_res, r.fec_ent_res, r.fec_sal_res,
                       u.nom_usu AS huesped_nombre
                FROM habitacion h
                LEFT JOIN detalle d ON d.cod_hab_det = h.cod_hab
                LEFT JOIN reservas r ON r.cod_res = d.cod_res_det
                    AND r.est_res IN ('Pendiente', 'Confirmada', 'En Casa')
                LEFT JOIN usuario u ON u.id_usu = r.id_usu_res
                ORDER BY h.num_hab ASC, r.cod_res DESC";
        $resultado = $conexion->query($sql);
        if (!$resultado) {
            throw new RuntimeException('No se pudieron consultar las habitaciones.');
        }

        $habitaciones = [];
        $vistos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $id = (int) $fila['cod_hab'];
            if (isset($vistos[$id])) {
                continue;
            }

            $habitaciones[] = [
                'id' => $id,
                'numero' => $fila['num_hab'],
                'tipo' => $fila['tipo_hab'],
                'precio' => $fila['pre_hab'],
                'estado' => $fila['est_hab'],
                'observacion' => $fila['obs_hab'],
                'huesped_nombre' => $fila['huesped_nombre'] ?? null,
                'fec_ent_res' => $fila['fec_ent_res'] ?? null,
                'fec_sal_res' => $fila['fec_sal_res'] ?? null,
                'est_res' => $fila['est_res'] ?? null,
                'cod_res' => $fila['cod_res'] ?? null,
            ];
            $vistos[$id] = true;
        }

        $resultado->free();
        return $habitaciones;
    }

    public function updateHabitacion(mysqli $conexion, int $id, string $estado, string $observacion): bool
    {
        $statement = $conexion->prepare(
            'UPDATE habitacion SET est_hab = ?, obs_hab = ? WHERE cod_hab = ?'
        );
        if (!$statement) {
            throw new RuntimeException('No se pudo preparar la actualización de habitación.');
        }

        $statement->bind_param('ssi', $estado, $observacion, $id);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('No se pudo actualizar la habitación.');
        }
        $ok = $statement->affected_rows > 0;
        $statement->close();
        return $ok;
    }

    public function fetchCreadorTarea(mysqli $conexion, int $idUsuario): ?array
    {
        $statement = $conexion->prepare(
            'SELECT u.nom_usu, u.cod_rol_usu, r.des_rol
             FROM usuario u
             LEFT JOIN rol r ON u.cod_rol_usu = r.cod_rol
             WHERE u.id_usu = ?'
        );
        if (!$statement) {
            throw new RuntimeException('No se pudo preparar la consulta del creador.');
        }

        $statement->bind_param('i', $idUsuario);
        $statement->execute();
        $creador = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $creador;
    }

    public function insertTarea(
        mysqli $conexion,
        string $titulo,
        string $categoria,
        string $descripcion,
        string $fecha,
        int $idCreador
    ): int {
        $estado = 'Pendiente';
        $statement = $conexion->prepare(
            'INSERT INTO tarea (tit_tar, cat_tar, des_tar, est_tar, fec_tar, cod_usu_tar)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$statement) {
            throw new RuntimeException('No se pudo preparar la creación de la tarea.');
        }

        $statement->bind_param('sssssi', $titulo, $categoria, $descripcion, $estado, $fecha, $idCreador);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('No se pudo guardar la tarea.');
        }

        $idTarea = (int) $statement->insert_id;
        $statement->close();
        return $idTarea;
    }

    public function fetchTareasPendientes(mysqli $conexion): array
    {
        $sql = "SELECT t.cod_tar, t.tit_tar, t.cat_tar, t.des_tar, t.prioridad_tar, t.fec_tar,
                       u.nom_usu, u.cod_rol_usu, r.des_rol
                FROM tarea t
                INNER JOIN usuario u ON t.cod_usu_tar = u.id_usu
                LEFT JOIN rol r ON u.cod_rol_usu = r.cod_rol
                WHERE t.est_tar = 'Pendiente'
                ORDER BY CASE WHEN t.cat_tar = 'URGENTE' THEN 1
                              WHEN t.cat_tar = 'LIMPIEZA' THEN 2 ELSE 3 END,
                         t.fec_tar ASC";
        $resultado = $conexion->query($sql);
        if (!$resultado) {
            throw new RuntimeException('No se pudieron consultar las tareas pendientes.');
        }

        $tareas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $rolNombre = (string) ($fila['des_rol'] ?: 'Personal');
            $creador = self::limpiarNombreCreador((string) $fila['nom_usu'], $rolNombre);
            $tareas[] = [
                'id' => $fila['cod_tar'],
                'titulo' => $fila['tit_tar'],
                'categoria' => $fila['cat_tar'],
                'descripcion' => $fila['des_tar'],
                'prioridad' => $fila['prioridad_tar'],
                'fecha' => $fila['fec_tar'],
                'creador' => $creador,
                'creador_formateado' => $creador . ' - ' . $rolNombre,
                'rol' => $fila['cod_rol_usu'],
                'rol_nombre' => $rolNombre,
            ];
        }

        $resultado->free();
        return $tareas;
    }

    public function completePendingTask(mysqli $conexion, int $idTarea): bool
    {
        $statement = $conexion->prepare(
            "UPDATE tarea SET est_tar = 'Completada'
             WHERE cod_tar = ? AND est_tar = 'Pendiente'"
        );
        if (!$statement) {
            throw new RuntimeException('No se pudo preparar la finalización de la tarea.');
        }

        $statement->bind_param('i', $idTarea);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('No se pudo completar la tarea.');
        }
        $ok = $statement->affected_rows > 0;
        $statement->close();
        return $ok;
    }

    public function updateTaskStatus(mysqli $conexion, int $idTarea, string $estado): bool
    {
        $statement = $conexion->prepare('UPDATE tarea SET est_tar = ? WHERE cod_tar = ?');
        if (!$statement) {
            throw new RuntimeException('No se pudo preparar la actualización de la tarea.');
        }

        $statement->bind_param('si', $estado, $idTarea);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('No se pudo actualizar la tarea.');
        }
        $ok = $statement->affected_rows > 0;
        $statement->close();
        return $ok;
    }

    private static function limpiarNombreCreador(string $nombre, string $rol): string
    {
        $nombre = trim($nombre);
        $rol = trim($rol);
        if ($rol !== '') {
            $nombre = (string) preg_replace('/\s+-?\s*' . preg_quote($rol, '/') . '$/iu', '', $nombre);
        }

        return trim($nombre) !== '' ? trim($nombre) : 'Usuario';
    }
}
