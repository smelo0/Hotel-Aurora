<?php
declare(strict_types=1);

namespace App\Experiencia;

final class ExperienceSchedule
{
    public static function exclusiones(array $schedules): array
    {
        $exclusions = $schedules['exclusiones'] ?? [];
        if (!is_array($exclusions)) {
            return [];
        }

        $result = [];
        foreach ([1, 2, 3] as $index) {
            $dates = $exclusions[(string) $index] ?? $exclusions[$index] ?? [];
            if (!is_array($dates)) {
                continue;
            }
            $result[(string) $index] = array_values(array_unique(array_filter(
                $dates,
                static fn($date): bool => is_string($date)
                    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            )));
            sort($result[(string) $index], SORT_STRING);
        }

        return $result;
    }

    public static function normalizar(array $schedules, array $options = []): array
    {
        $today = (new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota')))->format('Y-m-d');
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
                if (
                    !is_string($date)
                    || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                    || $date < $today
                ) {
                    continue;
                }
                if (is_array($range) && isset($range['inicio'], $range['fin'])) {
                    $ranges = [$range];
                } elseif (is_array($range) && array_is_list($range) && isset($range[0]) && is_array($range[0])) {
                    $ranges = $range;
                } elseif (is_array($range) && array_is_list($range)) {
                    $hours = array_values(array_filter(
                        $range,
                        static fn($hour): bool => is_string($hour)
                            && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hour) === 1
                    ));
                    sort($hours, SORT_STRING);
                    if ($hours === []) {
                        continue;
                    }
                    $ranges = [[
                        'inicio' => $hours[0],
                        'fin' => $hours[count($hours) - 1],
                    ]];
                } else {
                    continue;
                }
                $normalizedRanges = [];
                foreach ($ranges as $item) {
                    $start = is_array($item) ? ($item['inicio'] ?? null) : null;
                    $end = is_array($item) ? ($item['fin'] ?? null) : null;
                    if (
                        !is_string($start)
                        || !is_string($end)
                        || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start)
                        || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end)
                        || $end < $start
                    ) {
                        continue;
                    }
                    $normalizedRanges[] = ['inicio' => $start, 'fin' => $end];
                }
                if ($normalizedRanges === []) {
                    continue;
                }
                usort(
                    $normalizedRanges,
                    static fn(array $left, array $right): int => [$left['inicio'], $left['fin']] <=> [$right['inicio'], $right['fin']]
                );
                $result[(string) $index][$date] = $normalizedRanges;
            }
            ksort($result[(string) $index], SORT_STRING);
        }
        return $result;
    }
}
