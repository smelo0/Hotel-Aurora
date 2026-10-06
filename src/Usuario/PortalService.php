<?php
declare(strict_types=1);

namespace App\Usuario;

use mysqli;

final class PortalService
{
    public function __construct(private PortalRepository $repository)
    {
    }

    public function load(mysqli $connection, array $session): array
    {
        $user = $session['user_auth'] ?? $session['emp_auth'] ?? [];
        $userId = (int) ($user['id_usuario'] ?? 0);
        $userName = (string) ($user['nombre_usuario'] ?? '');
        $authenticated = $userId > 0 && (int) ($user['rol_usuario'] ?? 0) === 6;

        $experienceRepository = new \App\Experiencia\ExperienceRepository();
        $experiences = $experienceRepository->obtenerExperiencias($connection);
        $reservationStays = [];
        $reservationHistory = [];
        $experienceHistory = [];

        if ($authenticated) {
            $experienceHistory = $experienceRepository->obtenerHistorial($connection, $userId);
            $reservationStays = $this->repository->fetchReservationStays($connection, $userId);
            $reservationHistory = $this->repository->fetchReservationHistory($connection, $userId);
        }

        return [
            'habitaciones' => $this->repository->fetchRoomCatalog($connection),
            'experiencias' => $experiences,
            'usuarioId' => $userId,
            'usuarioNombre' => $userName,
            'usuarioAutenticado' => $authenticated,
            'usuarioTieneReserva' => $reservationStays !== [],
            'reservasEstanciaExperiencia' => $reservationStays,
            'historialReservas' => $reservationHistory,
            'historialExperiencias' => $experienceHistory,
        ];
    }
}
