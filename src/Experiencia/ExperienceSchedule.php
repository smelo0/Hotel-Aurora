<?php
declare(strict_types=1);

namespace App\Experiencia;

final class ExperienceSchedule
{
    public static function normalizar(array $schedules, array $options = []): array
    {
        if (isset($schedules['opciones']) && is_array($schedules['opciones'])) {
            $schedulesByOption = $schedules['opciones'];
        } elseif (isset($schedules['1']) || isset($schedules['2']) || isset($schedules['3'])) {
            $schedulesByOption = $schedules;
        } else {
            $schedulesByOption = [];
            foreach (array_keys($options) as $index) {
                $schedulesByOption[(string) ($index + 1)] = $schedules;
            }
        }

        $result = [];
        foreach ([1, 2, 3] as $index) {
            $dates = $schedulesByOption[(string) $index] ?? $schedulesByOption[$index] ?? [];
            if (!is_array($dates)) {
                $dates = [];
            }
            $result[(string) $index] = [];
            foreach ($dates as $date => $range) {
                if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    continue;
                }
                if (is_array($range) && array_is_list($range)) {
                    $hours = array_values(array_filter(
                        $range,
                        static fn($hour): bool => is_string($hour)
                            && preg_match('/^(?:[01]\d|2[0-3]):(?:00|30)$/', $hour) === 1
                    ));
                    sort($hours, SORT_STRING);
                    if ($hours === []) {
                        continue;
                    }
                    $start = $hours[0];
                    $end = $hours[count($hours) - 1];
                } elseif (is_array($range)) {
                    $start = $range['inicio'] ?? null;
                    $end = $range['fin'] ?? null;
                } else {
                    continue;
                }
                if (
                    !is_string($start)
                    || !is_string($end)
                    || !preg_match('/^(?:[01]\d|2[0-3]):(?:00|30)$/', $start)
                    || !preg_match('/^(?:[01]\d|2[0-3]):(?:00|30)$/', $end)
                    || $end < $start
                ) {
                    continue;
                }
                $result[(string) $index][$date] = ['inicio' => $start, 'fin' => $end];
            }
            ksort($result[(string) $index], SORT_STRING);
        }
        return $result;
    }
}
