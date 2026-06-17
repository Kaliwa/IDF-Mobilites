<?php

namespace App\Service;

use App\Entity\Journey;

final class DisruptionService
{
    public function __construct(
        private readonly PrimClient $primClient,
        private readonly NavitiaDisruptionClient $navitiaDisruptionClient,
        private readonly LineRefResolver $lineRefResolver,
        private readonly CurrentDisruptionClassifier $classifier,
    ) {
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function fetchCurrentForJourney(Journey $journey): array
    {
        return $this->fetchForJourney($journey)['currentDisruptions'];
    }

    /**
     * @return array{
     *   checkedLines: list<array<string,mixed>>,
     *   currentDisruptions: list<array<string,mixed>>,
     *   plannedDisruptions: list<array<string,mixed>>,
     *   disruptions: list<array<string,mixed>>
     * }
     */
    public function fetchForJourney(Journey $journey): array
    {
        $lineEntries = $this->normalizeLineEntries($journey->getLines() ?? []);
        if ($lineEntries === []) {
            return [
                'checkedLines' => [],
                'currentDisruptions' => [],
                'plannedDisruptions' => [],
                'disruptions' => [],
            ];
        }

        $checkedLines = [];
        $currentDisruptions = [];
        $plannedDisruptions = [];
        $seen = [];

        foreach ($lineEntries as $entry) {
            $resolved = $this->lineRefResolver->resolve($entry);
            $checkedLines[] = [
                'code' => $resolved['code'],
                'lineName' => $resolved['lineName'],
                'mode' => $resolved['mode'],
                'network' => $resolved['network'],
                'primRef' => $resolved['primRef'],
                'lineId' => $resolved['lineId'],
                'resolved' => $resolved['resolved'],
                'error' => $resolved['error'],
            ];

            if (!$resolved['resolved'] || $resolved['primRef'] === null) {
                continue;
            }

            foreach ($this->primClient->getDisruptions($resolved['primRef']) as $primEntry) {
                $bucket = $this->classifier->classifyPrim($primEntry);
                if ($bucket === 'past') {
                    continue;
                }

                $key = 'prim:' . $primEntry['id'];
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $item = $this->mapPrimDisruption($primEntry, $resolved, $bucket);
                if ($bucket === 'current') {
                    $currentDisruptions[] = $item;
                } else {
                    $plannedDisruptions[] = $item;
                }
            }

            if ($resolved['lineId'] !== null) {
                foreach ($this->navitiaDisruptionClient->getForLine($resolved['lineId']) as $navitiaEntry) {
                    $bucket = $this->classifier->classifyNavitia($navitiaEntry);
                    if ($bucket === 'past') {
                        continue;
                    }

                    $key = 'navitia:' . $navitiaEntry['id'];
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;

                    $item = $this->mapNavitiaDisruption($navitiaEntry, $resolved, $bucket);
                    if ($bucket === 'current') {
                        $currentDisruptions[] = $item;
                    } else {
                        $plannedDisruptions[] = $item;
                    }
                }
            }
        }

        $sort = static fn (array $a, array $b): int => strcmp((string) ($b['updatedAt'] ?? ''), (string) ($a['updatedAt'] ?? ''));

        usort($currentDisruptions, $sort);
        usort($plannedDisruptions, $sort);

        return [
            'checkedLines' => $checkedLines,
            'currentDisruptions' => $currentDisruptions,
            'plannedDisruptions' => $plannedDisruptions,
            'disruptions' => $currentDisruptions,
        ];
    }

    /**
     * @param array{id:string,text:string,detail?:?string,validUntil?:?\DateTimeImmutable,recordedAt:\DateTimeImmutable} $entry
     * @param array<string,mixed> $resolved
     *
     * @return array<string,mixed>
     */
    private function mapPrimDisruption(array $entry, array $resolved, string $bucket): array
    {
        return [
            'id' => $entry['id'],
            'line' => $resolved['code'],
            'lineName' => $resolved['lineName'],
            'mode' => $resolved['mode'],
            'network' => $resolved['network'],
            'status' => $bucket === 'current' ? 'Incident en cours' : 'Travaux / info à venir',
            'severity' => $bucket === 'current' ? 'perturbée' : 'info',
            'effect' => null,
            'message' => $entry['text'],
            'detail' => $entry['detail'] ?? null,
            'cause' => $bucket === 'current' ? 'incident' : 'travaux',
            'category' => $bucket === 'current' ? 'Incident' : 'Information',
            'disruptionStatus' => $bucket,
            'eligibleForJustificatif' => $bucket === 'current',
            'source' => 'prim',
            'updatedAt' => $entry['recordedAt']->format(\DateTimeInterface::ATOM),
            'validUntil' => $entry['validUntil']?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string,mixed> $entry
     * @param array<string,mixed> $resolved
     *
     * @return array<string,mixed>
     */
    private function mapNavitiaDisruption(array $entry, array $resolved, string $bucket): array
    {
        return [
            'id' => $entry['id'],
            'line' => $resolved['code'],
            'lineName' => $resolved['lineName'],
            'mode' => $resolved['mode'],
            'network' => $resolved['network'],
            'status' => $entry['severity'] ?? ($bucket === 'current' ? 'Incident en cours' : 'Travaux / info à venir'),
            'severity' => $entry['severity'],
            'effect' => $entry['effect'],
            'message' => $entry['text'],
            'detail' => $entry['detail'],
            'cause' => $entry['cause'],
            'category' => $entry['category'],
            'disruptionStatus' => $bucket,
            'eligibleForJustificatif' => $bucket === 'current',
            'source' => 'navitia',
            'updatedAt' => $entry['updatedAt']->format(\DateTimeInterface::ATOM),
            'validFrom' => $entry['validFrom']?->format(\DateTimeInterface::ATOM),
            'validUntil' => $entry['validUntil']?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param list<mixed> $lines
     *
     * @return list<array{code:string,mode?:string|null,network?:string|null,lineId?:string|null,primRef?:string|null}>
     */
    private function normalizeLineEntries(array $lines): array
    {
        $entries = [];

        foreach ($lines as $line) {
            if (is_string($line) && trim($line) !== '') {
                $entries[] = ['code' => trim($line)];
                continue;
            }

            if (!is_array($line)) {
                continue;
            }

            $code = trim((string) ($line['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $entries[] = [
                'code' => $code,
                'mode' => isset($line['mode']) ? (string) $line['mode'] : null,
                'network' => isset($line['network']) ? (string) $line['network'] : null,
                'lineId' => isset($line['lineId']) ? (string) $line['lineId'] : null,
                'primRef' => isset($line['primRef']) ? (string) $line['primRef'] : null,
            ];
        }

        return $entries;
    }
}
