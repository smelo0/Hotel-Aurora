<?php
declare(strict_types=1);

namespace App\Empleado;

use InvalidArgumentException;

final class EmpleadoService
{
    public const ESTADOS_HABITACION = [
        'Limpio',
        'Disponible',
        'Ocupada',
        'Sucia',
        'Mantenimiento',
    ];

    public const PRIORIDADES_MANTENIMIENTO = [
        'Urgente',
        'Importante',
        'No urgente',
    ];

    public const CATEGORIAS_TAREA = [
        'LIMPIEZA',
        'URGENTE',
        'GENERAL',
    ];

    public static function crearObservacionHabitacion(
        string $estado,
        string $prioridad,
        string $descripcion
    ): string {
        if (!in_array($estado, self::ESTADOS_HABITACION, true)) {
            throw new InvalidArgumentException('El estado de habitación no es válido.');
        }

        if ($estado !== 'Mantenimiento') {
            return '';
        }

        if (!in_array($prioridad, self::PRIORIDADES_MANTENIMIENTO, true)) {
            throw new InvalidArgumentException('La prioridad de mantenimiento no es válida.');
        }

        $descripcion = trim($descripcion);
        if ($descripcion === '' || self::contarCaracteres($descripcion) > 255) {
            throw new InvalidArgumentException('La descripción de mantenimiento no es válida.');
        }

        return 'Prioridad: ' . $prioridad . "\n" . $descripcion;
    }

    public static function validarDatosTarea(string $titulo, string $categoria, string $descripcion): void
    {
        if ($titulo === '' || self::contarCaracteres($titulo) > 100) {
            throw new InvalidArgumentException('El título debe tener entre 1 y 100 caracteres.');
        }
        if (!in_array($categoria, self::CATEGORIAS_TAREA, true)) {
            throw new InvalidArgumentException('La categoría de tarea no es válida.');
        }
        if ($descripcion === '' || self::contarCaracteres($descripcion) > 5000) {
            throw new InvalidArgumentException('La descripción de tarea no es válida.');
        }
    }

    private static function contarCaracteres(string $valor): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($valor, 'UTF-8');
        }

        $caracteres = preg_match_all('/./us', $valor);
        return $caracteres === false ? PHP_INT_MAX : $caracteres;
    }
}
