<?php
declare(strict_types=1);

function asegurar_esquema_experiencias(mysqli $conexion): void
{
    $conexion->query(
        "CREATE TABLE IF NOT EXISTS experiencias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            categoria VARCHAR(50) NOT NULL,
            nombre VARCHAR(150) NOT NULL,
            descripcion TEXT NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $columnas = [
        'imagen' => 'ALTER TABLE experiencias ADD COLUMN imagen VARCHAR(255) NULL',
        'opcion_1' => "ALTER TABLE experiencias ADD COLUMN opcion_1 VARCHAR(100) NOT NULL DEFAULT ''",
        'opcion_2' => "ALTER TABLE experiencias ADD COLUMN opcion_2 VARCHAR(100) NOT NULL DEFAULT ''",
        'opcion_3' => "ALTER TABLE experiencias ADD COLUMN opcion_3 VARCHAR(100) NOT NULL DEFAULT ''",
        'precio_opcion_1' => 'ALTER TABLE experiencias ADD COLUMN precio_opcion_1 DECIMAL(12,2) NULL',
        'precio_opcion_2' => 'ALTER TABLE experiencias ADD COLUMN precio_opcion_2 DECIMAL(12,2) NULL',
        'precio_opcion_3' => 'ALTER TABLE experiencias ADD COLUMN precio_opcion_3 DECIMAL(12,2) NULL',
        'horarios_json' => 'ALTER TABLE experiencias ADD COLUMN horarios_json TEXT NULL',
    ];

    foreach ($columnas as $columna => $sql) {
        $resultado = $conexion->query("SHOW COLUMNS FROM experiencias LIKE '{$columna}'");
        if ($resultado->num_rows === 0) {
            $conexion->query($sql);
        }
    }
}

function asegurar_esquema_agenda_experiencias(mysqli $conexion): void
{
    $conexion->query(
        "CREATE TABLE IF NOT EXISTS agenda_actividad (
            id_agenda BIGINT AUTO_INCREMENT PRIMARY KEY,
            actividad VARCHAR(150) NOT NULL,
            opcion_actividad VARCHAR(100) NOT NULL DEFAULT '',
            selecciones_personas_json TEXT NULL,
            fecha_agenda DATE NULL,
            hora_agenda TIME NULL,
            nombre_contacto VARCHAR(140) NOT NULL,
            correo_contacto VARCHAR(140) NOT NULL,
            id_usu_agenda BIGINT NULL,
            estado_agenda VARCHAR(30) DEFAULT 'Pendiente',
            estado_pago_experiencia VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
            fecha_pago_experiencia DATETIME NULL,
            metodo_pago_experiencia VARCHAR(30) NULL,
            cod_res_agenda BIGINT NULL,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $columnas = [
        'opcion_actividad' => "ALTER TABLE agenda_actividad ADD COLUMN opcion_actividad VARCHAR(100) NOT NULL DEFAULT '' AFTER actividad",
        'selecciones_personas_json' => 'ALTER TABLE agenda_actividad ADD COLUMN selecciones_personas_json TEXT NULL AFTER opcion_actividad',
        'fecha_agenda' => 'ALTER TABLE agenda_actividad MODIFY fecha_agenda DATE NULL',
        'hora_agenda' => 'ALTER TABLE agenda_actividad MODIFY hora_agenda TIME NULL',
        'id_usu_agenda' => 'ALTER TABLE agenda_actividad ADD COLUMN id_usu_agenda BIGINT NULL',
        'estado_agenda' => "ALTER TABLE agenda_actividad ADD COLUMN estado_agenda VARCHAR(30) DEFAULT 'Pendiente'",
        'estado_pago_experiencia' => "ALTER TABLE agenda_actividad ADD COLUMN estado_pago_experiencia VARCHAR(20) NOT NULL DEFAULT 'Pendiente'",
        'fecha_pago_experiencia' => 'ALTER TABLE agenda_actividad ADD COLUMN fecha_pago_experiencia DATETIME NULL',
        'metodo_pago_experiencia' => 'ALTER TABLE agenda_actividad ADD COLUMN metodo_pago_experiencia VARCHAR(30) NULL',
        'cod_res_agenda' => 'ALTER TABLE agenda_actividad ADD COLUMN cod_res_agenda BIGINT NULL',
        'monto_experiencia' => 'ALTER TABLE agenda_actividad ADD COLUMN monto_experiencia DECIMAL(12,2) NULL',
        'precios_personas_json' => 'ALTER TABLE agenda_actividad ADD COLUMN precios_personas_json TEXT NULL',
    ];
    $nuevaRelacionReserva = false;
    foreach ($columnas as $columna => $sql) {
        $resultado = $conexion->query("SHOW COLUMNS FROM agenda_actividad LIKE '{$columna}'");
        if ($resultado->num_rows === 0) {
            $conexion->query($sql);
            if ($columna === 'cod_res_agenda') {
                $nuevaRelacionReserva = true;
            }
        }
    }

    $indiceReserva = $conexion->query("SHOW INDEX FROM agenda_actividad WHERE Key_name = 'idx_agenda_reserva'");
    if ($indiceReserva->num_rows === 0) {
        $conexion->query('CREATE INDEX idx_agenda_reserva ON agenda_actividad (cod_res_agenda)');
    }

    if ($nuevaRelacionReserva) {
        $conexion->query(
            "UPDATE agenda_actividad a
             SET a.cod_res_agenda = (
                 SELECT r.cod_res
                 FROM reservas r
                 WHERE r.id_usu_res = a.id_usu_agenda
                   AND r.est_res NOT IN ('Cancelada', 'Cancelado')
                   AND DATE(r.fec_ent_res) <= a.fecha_agenda
                   AND DATE(r.fec_sal_res) > a.fecha_agenda
                 ORDER BY r.fec_ent_res DESC, r.cod_res DESC
                 LIMIT 1
             )
             WHERE a.cod_res_agenda IS NULL AND a.id_usu_agenda IS NOT NULL"
        );
    }

    $columnaActividad = $conexion->query("SHOW COLUMNS FROM agenda_actividad LIKE 'actividad'");
    $definicionActividad = $columnaActividad->fetch_assoc();
    if ($definicionActividad && preg_match('/varchar\((\d+)\)/i', (string) $definicionActividad['Type'], $coincidencia) && (int) $coincidencia[1] < 150) {
        $conexion->query('ALTER TABLE agenda_actividad MODIFY actividad VARCHAR(150) NOT NULL');
    }
}

function obtener_historial_experiencias(mysqli $conexion, ?int $idUsuario = null): array
{
    $sql = "SELECT a.id_agenda, a.actividad, a.opcion_actividad, a.selecciones_personas_json,
                   a.fecha_agenda, a.hora_agenda, a.nombre_contacto, a.correo_contacto,
                   a.estado_agenda, a.estado_pago_experiencia, a.fecha_pago_experiencia, a.metodo_pago_experiencia, a.creado_en, a.monto_experiencia, a.precios_personas_json,
                   a.id_usu_agenda, a.cod_res_agenda,
                   (COALESCE(a.estado_agenda, 'Pendiente') <> 'Cancelada'
                    AND a.creado_en >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                    AND TIMESTAMP(a.fecha_agenda, a.hora_agenda) > NOW()) AS puede_cancelar,
                   GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(a.creado_en, INTERVAL 5 MINUTE))) AS segundos_cancelacion,
                   COALESCE(NULLIF(u.nom_usu, ''), a.nombre_contacto) AS nombre_cliente,
                   COALESCE(NULLIF(u.corr_usu, ''), a.correo_contacto) AS correo_cliente
            FROM agenda_actividad a
            LEFT JOIN usuario u ON u.id_usu = a.id_usu_agenda";
    if ($idUsuario !== null) {
        $sql .= ' WHERE a.id_usu_agenda = ?';
    }
    $sql .= ' ORDER BY a.creado_en DESC, a.id_agenda DESC';

    $stmt = $conexion->prepare($sql);
    if ($idUsuario !== null) {
        $stmt->bind_param('i', $idUsuario);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
    $solicitudes = [];
    while ($fila = $resultado->fetch_assoc()) {
        $selecciones = json_decode((string) ($fila['selecciones_personas_json'] ?? ''), true);
        $fila['selecciones_personas'] = is_array($selecciones) ? $selecciones : [];
        $precios = json_decode((string) ($fila['precios_personas_json'] ?? ''), true);
        $fila['precios_personas'] = is_array($precios) ? $precios : [];
        $fila['id_agenda'] = (int) $fila['id_agenda'];
        unset($fila['selecciones_personas_json'], $fila['precios_personas_json']);
        $solicitudes[] = $fila;
    }
    $stmt->close();
    return $solicitudes;
}

function normalizar_horarios_experiencia(array $horarios, array $opciones = []): array
{
    $resultado = [];
    if (isset($horarios['opciones']) && is_array($horarios['opciones'])) {
        $horariosPorOpcion = $horarios['opciones'];
    } elseif (isset($horarios['1']) || isset($horarios['2']) || isset($horarios['3'])) {
        $horariosPorOpcion = $horarios;
    } else {
        $horariosPorOpcion = [];
        foreach (array_keys($opciones) as $indice) {
            $horariosPorOpcion[(string) ($indice + 1)] = $horarios;
        }
    }

    foreach ([1, 2, 3] as $indice) {
        $fechas = $horariosPorOpcion[(string) $indice] ?? $horariosPorOpcion[$indice] ?? [];
        if (!is_array($fechas)) {
            $fechas = [];
        }
        $resultado[(string) $indice] = [];
        foreach ($fechas as $fecha => $rango) {
            if (!is_string($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                continue;
            }
            if (is_array($rango) && array_is_list($rango)) {
                $horas = array_values(array_filter($rango, static fn($hora): bool => is_string($hora) && preg_match('/^(?:[01]\d|2[0-3]):(?:00|30)$/', $hora) === 1));
                sort($horas, SORT_STRING);
                if ($horas === []) {
                    continue;
                }
                $inicio = $horas[0];
                $fin = $horas[count($horas) - 1];
            } elseif (is_array($rango)) {
                $inicio = $rango['inicio'] ?? null;
                $fin = $rango['fin'] ?? null;
            } else {
                continue;
            }
            if (!is_string($inicio) || !is_string($fin)
                || !preg_match('/^(?:[01]\d|2[0-3]):(?:00|30)$/', $inicio)
                || !preg_match('/^(?:[01]\d|2[0-3]):(?:00|30)$/', $fin)
                || $fin < $inicio) {
                continue;
            }
            $resultado[(string) $indice][$fecha] = ['inicio' => $inicio, 'fin' => $fin];
        }
        ksort($resultado[(string) $indice], SORT_STRING);
    }
    return $resultado;
}

function obtener_experiencias(mysqli $conexion): array
{
    $resultado = $conexion->query(
        'SELECT id, categoria, nombre, descripcion, imagen, opcion_1, opcion_2, opcion_3,
                precio_opcion_1, precio_opcion_2, precio_opcion_3, horarios_json
         FROM experiencias ORDER BY id ASC'
    );
    $experiencias = [];

    while ($fila = $resultado->fetch_assoc()) {
        $fila['id'] = (int) $fila['id'];
        $fila['opciones'] = [];
        $fila['precios'] = [];
        $fila['numeros_opciones'] = [];
        foreach ([1, 2, 3] as $indice) {
            $opcion = trim((string) $fila['opcion_' . $indice]);
            if ($opcion === '') {
                continue;
            }
            $fila['opciones'][] = $opcion;
            $fila['numeros_opciones'][] = $indice;
            $precio = $fila['precio_opcion_' . $indice];
            $fila['precios'][] = $precio === null ? null : number_format((float) $precio, 2, '.', '');
        }
        $fila['horarios'] = json_decode((string) ($fila['horarios_json'] ?? ''), true);
        if (!is_array($fila['horarios'])) {
            $fila['horarios'] = [];
        } else {
            $fila['horarios'] = normalizar_horarios_experiencia($fila['horarios'], $fila['opciones']);
        }
        unset($fila['opcion_1'], $fila['opcion_2'], $fila['opcion_3'], $fila['precio_opcion_1'], $fila['precio_opcion_2'], $fila['precio_opcion_3'], $fila['horarios_json']);
        $experiencias[] = $fila;
    }

    return $experiencias;
}
