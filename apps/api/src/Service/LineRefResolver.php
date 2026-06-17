<?php

namespace App\Service;

use App\Entity\TransitLine;
use App\Repository\TransitLineRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LineRefResolver
{
    public function __construct(
        private readonly TransitLineRepository $transitLineRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey = null,
        private readonly ?string $navitiaBaseUrl = null,
    ) {
    }

    /**
     * @param array{code:string,mode?:string|null,network?:string|null,lineId?:string|null,primRef?:string|null} $input
     *
     * @return array{
     *   code:string,
     *   mode:?string,
     *   network:?string,
     *   lineName:?string,
     *   lineId:?string,
     *   primRef:?string,
     *   resolved:bool,
     *   error:?string
     * }
     */
    public function resolve(array $input): array
    {
        $code = trim((string) ($input['code'] ?? ''));
        $mode = isset($input['mode']) ? trim((string) $input['mode']) : null;
        $network = isset($input['network']) ? trim((string) $input['network']) : null;
        $lineId = isset($input['lineId']) ? trim((string) $input['lineId']) : null;
        $primRef = isset($input['primRef']) ? trim((string) $input['primRef']) : null;

        if ($code === '' && $primRef === null && $lineId === null) {
            return $this->unresolved($code, $mode, $network, 'Code de ligne manquant.');
        }

        if ($primRef !== null && $primRef !== '') {
            $lineId ??= $this->lineIdFromPrimRef($primRef);

            return [
                'code' => $code !== '' ? $code : $this->codeFromLineId($lineId),
                'mode' => $mode,
                'network' => $network,
                'lineName' => null,
                'lineId' => $lineId,
                'primRef' => $this->normalizePrimRef($primRef),
                'resolved' => true,
                'error' => null,
            ];
        }

        if ($lineId !== null && $lineId !== '') {
            return [
                'code' => $code !== '' ? $code : $this->codeFromLineId($lineId),
                'mode' => $mode,
                'network' => $network,
                'lineName' => null,
                'lineId' => $lineId,
                'primRef' => $this->primRefFromLineId($lineId),
                'resolved' => true,
                'error' => null,
            ];
        }

        if ($this->looksLikePrimRef($code)) {
            return $this->resolve([
                'code' => $this->codeFromPrimRef($code),
                'mode' => $mode,
                'network' => $network,
                'primRef' => $this->normalizePrimRef($code),
            ]);
        }

        $fromDb = $this->resolveFromDatabase($code, $mode);
        if ($fromDb !== null) {
            return $fromDb;
        }

        $fromNavitia = $this->resolveFromNavitia($code, $mode, $network);
        if ($fromNavitia !== null) {
            return $fromNavitia;
        }

        return $this->unresolved(
            $code,
            $mode,
            $network,
            sprintf('Impossible de résoudre la ligne « %s » vers une référence PRIM.', $code),
        );
    }

    private function resolveFromDatabase(string $code, ?string $mode): ?array
    {
        $candidates = array_values(array_unique(array_filter([
            strtolower($code),
            strtolower(str_replace(' ', '-', $code)),
            preg_replace('/^m(étro|etro)?\s*/iu', '', $code) ?? $code,
            preg_replace('/^rer\s*/iu', '', $code) ?? $code,
            preg_replace('/^tram\s*/iu', '', $code) ?? $code,
        ])));

        foreach ($candidates as $candidate) {
            $line = $this->transitLineRepository->findOneBy(['code' => $candidate]);
            if (!$line instanceof TransitLine || null === $line->getPrimRef()) {
                continue;
            }

            if ($mode !== null && !$this->modeMatches($mode, (string) $line->getName())) {
                continue;
            }

            return $this->fromTransitLine($line, $code, $mode);
        }

        $nameQuery = $this->transitLineRepository->createQueryBuilder('line')
            ->where('LOWER(line.name) LIKE :name')
            ->setParameter('name', '%' . strtolower($code) . '%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        foreach ($nameQuery as $line) {
            if (!$line instanceof TransitLine || null === $line->getPrimRef()) {
                continue;
            }
            if ($mode !== null && !$this->modeMatches($mode, (string) $line->getName())) {
                continue;
            }

            return $this->fromTransitLine($line, $code, $mode);
        }

        return null;
    }

    private function resolveFromNavitia(string $code, ?string $mode, ?string $network): ?array
    {
        if (!$this->apiKey || !$this->navitiaBaseUrl) {
            return null;
        }

        $physicalMode = $this->physicalModeFilter($mode);
        $paths = [];
        if ($physicalMode !== null) {
            $paths[] = sprintf(
                '%s/physical_modes/%s/lines?filter=line.code=%s&count=5',
                rtrim($this->navitiaBaseUrl, '/'),
                rawurlencode($physicalMode),
                rawurlencode($code),
            );
        }

        $paths[] = sprintf(
            '%s/lines?filter=line.code=%s&count=10',
            rtrim($this->navitiaBaseUrl, '/'),
            rawurlencode($code),
        );

        foreach ($paths as $path) {
            try {
                $response = $this->httpClient->request('GET', $path, [
                    'headers' => [
                        'apikey' => $this->apiKey,
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 8.0,
                ]);
                $data = $response->toArray(false);
            } catch (\Throwable) {
                continue;
            }

            $lines = $data['lines'] ?? [];
            if (!is_array($lines) || $lines === []) {
                continue;
            }

            $best = $this->pickBestNavitiaLine($lines, $mode, $network);
            if ($best === null) {
                continue;
            }

            $lineId = (string) ($best['id'] ?? '');
            $primRef = $this->primRefFromNavitiaLine($best) ?? $this->primRefFromLineId($lineId);

            return [
                'code' => (string) ($best['code'] ?? $code),
                'mode' => (string) ($best['commercial_mode']['name'] ?? $mode),
                'network' => (string) ($best['network']['name'] ?? $network),
                'lineName' => (string) ($best['name'] ?? $code),
                'lineId' => $lineId !== '' ? $lineId : null,
                'primRef' => $primRef,
                'resolved' => $primRef !== null,
                'error' => $primRef !== null ? null : 'Référence PRIM introuvable dans Navitia.',
            ];
        }

        return null;
    }

    /**
     * @param list<array<string,mixed>> $lines
     */
    private function pickBestNavitiaLine(array $lines, ?string $mode, ?string $network): ?array
    {
        $scored = [];
        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }

            $score = 0;
            $commercialMode = strtolower((string) ($line['commercial_mode']['name'] ?? ''));
            $lineNetwork = strtolower((string) ($line['network']['name'] ?? ''));

            if ($mode !== null && str_contains($commercialMode, strtolower($mode))) {
                $score += 10;
            }
            if ($network !== null && str_contains($lineNetwork, strtolower($network))) {
                $score += 5;
            }
            if ($commercialMode !== '' && !str_contains(strtolower($mode ?? ''), 'bus') && $commercialMode === 'bus') {
                $score -= 3;
            }

            $scored[] = ['line' => $line, 'score' => $score];
        }

        if ($scored === []) {
            return null;
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return $scored[0]['line'];
    }

    private function fromTransitLine(TransitLine $line, string $code, ?string $mode): array
    {
        $primRef = $this->normalizePrimRef((string) $line->getPrimRef());
        $lineId = $this->lineIdFromPrimRef($primRef);

        return [
            'code' => $code !== '' ? $code : (string) ($line->getCode() ?? ''),
            'mode' => $mode ?? $this->modeFromLineName((string) $line->getName()),
            'network' => null,
            'lineName' => (string) $line->getName(),
            'lineId' => $lineId,
            'primRef' => $primRef,
            'resolved' => true,
            'error' => null,
        ];
    }

    /**
     * @param array<string,mixed> $line
     */
    private function primRefFromNavitiaLine(array $line): ?string
    {
        foreach ($line['codes'] ?? [] as $code) {
            if (!is_array($code)) {
                continue;
            }
            $value = (string) ($code['value'] ?? '');
            if (preg_match('/C\d+/', $value, $matches)) {
                return 'STIF:Line::' . $matches[0] . ':';
            }
        }

        return $this->primRefFromLineId((string) ($line['id'] ?? ''));
    }

    private function primRefFromLineId(?string $lineId): ?string
    {
        if ($lineId === null || $lineId === '') {
            return null;
        }
        if (preg_match('/C\d+/', $lineId, $matches)) {
            return 'STIF:Line::' . $matches[0] . ':';
        }

        return null;
    }

    private function lineIdFromPrimRef(string $primRef): ?string
    {
        if (preg_match('/C\d+/', $primRef, $matches)) {
            return 'line:IDFM:' . $matches[0];
        }

        return null;
    }

    private function codeFromLineId(?string $lineId): string
    {
        return $lineId !== null && preg_match('/C(\d+)/', $lineId, $matches)
            ? $matches[1]
            : '';
    }

    private function codeFromPrimRef(string $primRef): string
    {
        return preg_match('/C(\d+)/', $primRef, $matches) ? $matches[1] : '';
    }

    private function normalizePrimRef(string $primRef): string
    {
        if (preg_match('/C\d+/', $primRef, $matches)) {
            return 'STIF:Line::' . $matches[0] . ':';
        }

        return $primRef;
    }

    private function looksLikePrimRef(string $value): bool
    {
        return str_contains($value, 'STIF:Line::') || str_contains($value, 'FR1:Line:');
    }

    private function physicalModeFilter(?string $mode): ?string
    {
        if ($mode === null || $mode === '') {
            return null;
        }

        $normalized = strtolower($mode);
        if (str_contains($normalized, 'métro') || str_contains($normalized, 'metro')) {
            return 'physical_mode:Metro';
        }
        if (str_contains($normalized, 'rer') || str_contains($normalized, 'transilien')) {
            return 'physical_mode:Train';
        }
        if (str_contains($normalized, 'tram')) {
            return 'physical_mode:Tramway';
        }
        if (str_contains($normalized, 'bus')) {
            return 'physical_mode:Bus';
        }

        return null;
    }

    private function modeMatches(string $requestedMode, string $lineName): bool
    {
        $requested = strtolower($requestedMode);
        $name = strtolower($lineName);

        if (str_contains($requested, 'métro') || str_contains($requested, 'metro')) {
            return str_contains($name, 'métro') || str_contains($name, 'metro');
        }
        if (str_contains($requested, 'rer')) {
            return str_contains($name, 'rer');
        }
        if (str_contains($requested, 'tram')) {
            return str_contains($name, 'tram');
        }
        if (str_contains($requested, 'bus')) {
            return str_contains($name, 'bus');
        }

        return true;
    }

    private function modeFromLineName(string $lineName): ?string
    {
        $name = strtolower($lineName);
        if (str_contains($name, 'métro') || str_contains($name, 'metro')) {
            return 'Métro';
        }
        if (str_contains($name, 'rer')) {
            return 'RER';
        }
        if (str_contains($name, 'tram')) {
            return 'Tramway';
        }

        return null;
    }

    /**
     * @return array{
     *   code:string,
     *   mode:?string,
     *   network:?string,
     *   lineName:?string,
     *   lineId:?string,
     *   primRef:?string,
     *   resolved:bool,
     *   error:?string
     * }
     */
    private function unresolved(string $code, ?string $mode, ?string $network, string $error): array
    {
        return [
            'code' => $code,
            'mode' => $mode,
            'network' => $network,
            'lineName' => null,
            'lineId' => null,
            'primRef' => null,
            'resolved' => false,
            'error' => $error,
        ];
    }
}
