<?php

namespace App\Service;

use App\Entity\TransitLine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TransitLinesImporter
{
    private const CATALOG_URL = 'https://data.iledefrance-mobilites.fr/api/explore/v2.1/catalog/datasets/referentiel-des-lignes/records';
    private const BATCH = 100;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @var array<string, true> */
    private array $usedCodes = [];

    /**
     * Fetches metro, RER and tram lines from IDFM open data and upserts them
     * in the database using primRef as the unique key.
     *
     * @return array{created:int,updated:int}
     */
    public function import(): array
    {
        $counts = ['created' => 0, 'updated' => 0];
        $offset = 0;
        $this->usedCodes = [];

        // Pre-load existing codes to detect collisions across batches
        foreach ($this->entityManager->getRepository(TransitLine::class)->findAll() as $line) {
            if (null !== $line->getCode()) {
                $this->usedCodes[$line->getCode()] = true;
            }
        }

        do {
            $records = $this->fetchPage($offset);

            foreach ($records as $record) {
                $this->upsert($record, $counts);
            }

            $this->entityManager->flush();
            $offset += self::BATCH;
        } while (count($records) === self::BATCH);

        return $counts;
    }

    /**
     * @return list<array{id:string,shortname:string,transportmode:string}>
     */
    private function fetchPage(int $offset): array
    {
        try {
            $response = $this->httpClient->request('GET', self::CATALOG_URL, [
                'query' => [
                    'where' => "transportmode IN ('rail','metro','tram') AND status='active'",
                    'limit' => self::BATCH,
                    'offset' => $offset,
                    'select' => 'id_line,shortname_line,name_line,transportmode',
                ],
                'timeout' => 15.0,
            ]);

            $data = $response->toArray();
        } catch (\Throwable) {
            return [];
        }

        $rows = [];
        foreach ($data['results'] ?? [] as $record) {
            $name = (string) ($record['name_line'] ?? '');
            if ('' === $name || str_contains($name, 'eplacement') || str_contains($name, 'uture')) {
                continue;
            }

            $id = (string) ($record['id_line'] ?? '');
            $shortname = (string) ($record['shortname_line'] ?? '');
            $mode = (string) ($record['transportmode'] ?? '');

            if ('' === $id || '' === $shortname) {
                continue;
            }

            // Ignore lignes TER / interurbaines : pas de pictogramme IDFM.
            // Leur shortname contient un espace ("TER C01746") ou est générique ("TER", "NAT").
            if (str_contains($shortname, ' ') || in_array(strtoupper($shortname), ['TER', 'NAT'], true)) {
                continue;
            }

            $rows[] = ['id' => $id, 'shortname' => $shortname, 'transportmode' => $mode];
        }

        return $rows;
    }

    /**
     * @param array{id:string,shortname:string,transportmode:string} $record
     * @param array{created:int,updated:int} $counts
     */
    private function upsert(array $record, array &$counts): void
    {
        $primRef = sprintf('STIF:Line::%s:', $record['id']);
        $baseCode = $record['shortname'];
        $name = $this->formatName($record['shortname'], $record['transportmode']);

        $repository = $this->entityManager->getRepository(TransitLine::class);

        // 1. Match by primRef (most specific)
        $line = $repository->findOneBy(['primRef' => $primRef]);

        if (null !== $line) {
            $existingCode = $line->getCode() ?? '';
            $line->setName($name);

            // Only move to baseCode if it's free (or already ours)
            if ($existingCode !== $baseCode) {
                if (!isset($this->usedCodes[$baseCode])) {
                    unset($this->usedCodes[$existingCode]);
                    $this->usedCodes[$baseCode] = true;
                    $line->setCode($baseCode);
                }
                // else: keep the existing suffixed code — don't move to an occupied slot
            }

            ++$counts['updated'];
            $this->entityManager->persist($line);
            return;
        }

        // 2. Match by code or same name (for bootstrapper-seeded lines) if no primRef yet
        $byCode = $repository->findOneBy(['code' => $baseCode]);
        if (!$byCode instanceof TransitLine) {
            $byCode = $repository->findOneBy(['name' => $name, 'primRef' => null]);
        }

        if ($byCode instanceof TransitLine && null === $byCode->getPrimRef()) {
            $oldCode = $byCode->getCode();
            if (null !== $oldCode) {
                unset($this->usedCodes[$oldCode]);
            }
            $this->usedCodes[$baseCode] = true;
            $byCode->setCode($baseCode)->setName($name)->setPrimRef($primRef);
            ++$counts['updated'];
            $this->entityManager->persist($byCode);
            return;
        }

        // 3. Create new line — ensure unique code when there is a code collision
        if (null !== $byCode || isset($this->usedCodes[$baseCode])) {
            $code = $baseCode . '-' . strtolower($record['id']);
        } else {
            $code = $baseCode;
        }

        $this->usedCodes[$code] = true;

        $line = (new TransitLine())
            ->setCode($code)
            ->setName($name)
            ->setPrimRef($primRef);

        ++$counts['created'];
        $this->entityManager->persist($line);
    }

    private function formatName(string $shortname, string $mode): string
    {
        return match ($mode) {
            'metro' => 'Métro ' . $shortname,
            'rail' => 'RER ' . $shortname,
            'tram' => 'Tram ' . $shortname,
            default => $shortname,
        };
    }
}
