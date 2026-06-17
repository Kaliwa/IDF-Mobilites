<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IdfmRouteService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey = null,
        private readonly ?string $navitiaBaseUrl = null,
        private readonly ?string $osrmUrl = null,
    ) {
    }

    /**
     * @param array{lat:float,lng:float,name:string} $origin
     * @param array{lat:float,lng:float,name:string} $destination
     * @return array<int,array<string,mixed>>
     */
    public function suggest(array $origin, array $destination): array
    {
        $navitia = $this->tryNavitia($origin, $destination);
        if ($navitia !== null && count($navitia) > 0) {
            return $navitia;
        }

        return $this->fallbackOsrm($origin, $destination);
    }

    /**
     * @param array{lat:float,lng:float,name:string} $origin
     * @param array{lat:float,lng:float,name:string} $destination
     * @return array<int,array<string,mixed>>|null
     */
    private function tryNavitia(array $origin, array $destination): ?array
    {
        if (!$this->apiKey || !$this->navitiaBaseUrl) {
            return null;
        }

        try {
            $url = sprintf(
                '%s/journeys?from=%f;%f&to=%f;%f&max_nb_journeys=3&count=3',
                rtrim($this->navitiaBaseUrl, '/'),
                $origin['lng'],
                $origin['lat'],
                $destination['lng'],
                $destination['lat'],
            );

            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'apikey' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'timeout' => 10.0,
            ]);

            $data = $response->toArray(false);
        } catch (\Throwable) {
            return null;
        }

        if (!isset($data['journeys']) || !is_array($data['journeys'])) {
            return null;
        }

        $results = [];
        foreach (array_slice($data['journeys'], 0, 3) as $index => $journey) {
            $parsed = $this->parseJourney($journey, $index);
            if ($parsed !== null) {
                $results[] = $parsed;
            }
        }

        return $results;
    }

    /**
     * @param array<string,mixed> $journey
     * @return array<string,mixed>|null
     */
    private function parseJourney(array $journey, int $index): ?array
    {
        $sections = $journey['sections'] ?? [];
        if (!is_array($sections)) {
            return null;
        }

        $segments = [];
        $lines = [];
        $polylines = [];
        $allCoords = [];
        $totalDistance = 0.0;
        $summaryParts = [];

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $type = (string) ($section['type'] ?? '');
            $display = is_array($section['display_informations'] ?? null)
                ? $section['display_informations']
                : [];
            $sectionCoords = $this->extractCoords($section);

            if (!empty($sectionCoords)) {
                $sectionDistance = $this->polylineLengthMeters($sectionCoords);
                $totalDistance += $sectionDistance;

                $color = $this->hexColor(
                    is_string($display['color'] ?? null) ? $display['color'] : null,
                    $type === 'street_network' ? '#64748b' : '#1972d2',
                );

                $polylines[] = [
                    'coords' => $sectionCoords,
                    'color' => $color,
                ];

                foreach ($sectionCoords as $coord) {
                    $allCoords[] = $coord;
                }
            }

            if ($type === 'public_transport') {
                $code = (string) ($display['code'] ?? $display['label'] ?? '');
                if ($code === '') {
                    continue;
                }

                $lineRef = $this->extractLineRef($section);
                $mode = (string) ($display['commercial_mode'] ?? $display['physical_mode'] ?? 'Transport');
                $segments[] = [
                    'mode' => $mode,
                    'physicalMode' => $display['physical_mode'] ?? null,
                    'code' => $code,
                    'label' => (string) ($display['label'] ?? $code),
                    'network' => $display['network'] ?? null,
                    'lineId' => $lineRef['lineId'] ?? null,
                    'primRef' => $lineRef['primRef'] ?? null,
                    'color' => $this->hexColor(
                        is_string($display['color'] ?? null) ? $display['color'] : null,
                        '#1972d2',
                    ),
                    'textColor' => $this->hexColor(
                        is_string($display['text_color'] ?? null) ? $display['text_color'] : null,
                        '#ffffff',
                    ),
                ];
                $lines[] = $code;
                $summaryParts[] = trim($mode . ' ' . $code);
            } elseif ($type === 'street_network' && isset($section['duration']) && (int) $section['duration'] > 0) {
                $walkMin = max(1, (int) round(((int) $section['duration']) / 60));
                $summaryParts[] = sprintf('%d min à pied', $walkMin);
            }
        }

        $durationMin = isset($journey['duration'])
            ? (int) round(((int) $journey['duration']) / 60)
            : 0;

        if ($totalDistance <= 0 && isset($journey['distances']) && is_array($journey['distances'])) {
            $totalDistance = (float) array_sum(array_map('floatval', $journey['distances']));
        }

        return [
            'label' => sprintf('Itinéraire %d', $index + 1),
            'duration' => $durationMin,
            'distanceKm' => round($totalDistance / 1000, 1),
            'lines' => array_values(array_unique(array_filter($lines))),
            'segments' => $segments,
            'summary' => $summaryParts !== [] ? implode(' → ', $summaryParts) : 'Calculé via PRIM',
            'coords' => $allCoords,
            'polylines' => $polylines,
        ];
    }

    /**
     * @param array<string,mixed> $section
     * @return array<int,array{0:float,1:float}>
     */
    private function extractCoords(array $section): array
    {
        $geometry = $section['geojson'] ?? $section['path'] ?? null;
        if (!is_array($geometry)) {
            return [];
        }

        $coordinates = $geometry['coordinates'] ?? [];
        if (!is_array($coordinates) || $coordinates === []) {
            return [];
        }

        $pairs = $coordinates;
        if (isset($coordinates[0][0]) && is_array($coordinates[0][0])) {
            $pairs = [];
            foreach ($coordinates as $line) {
                if (!is_array($line)) {
                    continue;
                }
                foreach ($line as $pair) {
                    $pairs[] = $pair;
                }
            }
        }

        $coords = [];
        foreach ($pairs as $pair) {
            if (!is_array($pair) || count($pair) < 2) {
                continue;
            }
            $coords[] = [(float) $pair[1], (float) $pair[0]];
        }

        return $coords;
    }

    /**
     * @param array<int,array{0:float,1:float}> $coords
     */
    private function polylineLengthMeters(array $coords): float
    {
        $total = 0.0;
        for ($i = 1, $count = count($coords); $i < $count; ++$i) {
            $total += $this->haversineMeters(
                $coords[$i - 1][0],
                $coords[$i - 1][1],
                $coords[$i][0],
                $coords[$i][1],
            );
        }

        return $total;
    }

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function hexColor(?string $value, string $fallback): string
    {
        if ($value === null || trim($value) === '') {
            return $fallback;
        }

        return '#' . ltrim(trim($value), '#');
    }

    /**
     * @param array<string,mixed> $section
     *
     * @return array{lineId:?string,primRef:?string}
     */
    private function extractLineRef(array $section): array
    {
        foreach ($section['links'] ?? [] as $link) {
            if (!is_array($link) || ($link['type'] ?? '') !== 'line') {
                continue;
            }

            $lineId = (string) ($link['id'] ?? '');
            if ($lineId === '') {
                continue;
            }

            $primRef = null;
            if (preg_match('/C\d+/', $lineId, $matches)) {
                $primRef = 'STIF:Line::' . $matches[0] . ':';
            }

            return ['lineId' => $lineId, 'primRef' => $primRef];
        }

        return ['lineId' => null, 'primRef' => null];
    }

    /**
     * @param array{lat:float,lng:float,name:string} $origin
     * @param array{lat:float,lng:float,name:string} $destination
     * @return array<int,array<string,mixed>>
     */
    private function fallbackOsrm(array $origin, array $destination): array
    {
        $base = $this->osrmUrl ?: 'https://router.project-osrm.org/route/v1/driving';
        $url = sprintf(
            '%s/%f,%f;%f,%f?overview=full&geometries=geojson&alternatives=true&steps=false',
            rtrim($base, '/'),
            $origin['lng'],
            $origin['lat'],
            $destination['lng'],
            $destination['lat'],
        );

        try {
            $response = $this->httpClient->request('GET', $url, ['timeout' => 8.0]);
            $data = $response->toArray(false);
        } catch (\Throwable) {
            return [];
        }

        $routes = $data['routes'] ?? [];
        if (!is_array($routes)) {
            return [];
        }

        $results = [];
        foreach (array_slice($routes, 0, 3) as $index => $route) {
            $coords = [];
            foreach (($route['geometry']['coordinates'] ?? []) as $pair) {
                if (is_array($pair) && count($pair) >= 2) {
                    $coords[] = [(float) $pair[1], (float) $pair[0]];
                }
            }

            $distanceKm = isset($route['distance']) ? round(((float) $route['distance']) / 1000, 1) : 0.0;
            $durationMin = isset($route['duration']) ? (int) round(((float) $route['duration']) / 60) : 0;

            $results[] = [
                'label' => sprintf('Itinéraire (voiture) %d', $index + 1),
                'duration' => $durationMin,
                'distanceKm' => $distanceKm,
                'lines' => [],
                'segments' => [],
                'summary' => 'Itinéraire routier (secours).',
                'coords' => $coords,
                'polylines' => [['coords' => $coords, 'color' => '#1972d2']],
            ];
        }

        return $results;
    }
}
