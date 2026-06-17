<?php

namespace App\Service;

final class CurrentDisruptionClassifier
{
    /**
     * @param array{
     *   text:string,
     *   detail?:?string,
     *   recordedAt?:\DateTimeImmutable,
     *   validUntil?:?\DateTimeImmutable
     * } $entry
     */
    public function classifyPrim(array $entry): string
    {
        $text = strtolower(trim($entry['text'] . ' ' . ($entry['detail'] ?? '')));

        if ($this->looksLikePlannedWork($text)) {
            return 'planned';
        }

        $now = new \DateTimeImmutable();
        $recordedAt = $entry['recordedAt'] ?? null;
        $validUntil = $entry['validUntil'] ?? null;

        if ($recordedAt instanceof \DateTimeImmutable && $recordedAt > $now->modify('-6 hours')) {
            if ($this->looksLikeCurrentIncident($text)) {
                return 'current';
            }
        }

        if ($validUntil instanceof \DateTimeImmutable && $validUntil > $now && $this->looksLikeCurrentIncident($text)) {
            return 'current';
        }

        return 'planned';
    }

    /**
     * @param array{
     *   status:string,
     *   text:string,
     *   validFrom?:?\DateTimeImmutable,
     *   validUntil?:?\DateTimeImmutable,
     *   updatedAt?:\DateTimeImmutable
     * } $entry
     */
    public function classifyNavitia(array $entry): string
    {
        $status = strtolower($entry['status']);

        if ($status === 'past') {
            return 'past';
        }

        if ($status === 'future') {
            return 'planned';
        }

        if ($status !== 'active') {
            return 'planned';
        }

        $now = new \DateTimeImmutable();
        $validFrom = $entry['validFrom'] ?? null;
        $validUntil = $entry['validUntil'] ?? null;

        if ($validFrom instanceof \DateTimeImmutable && $validUntil instanceof \DateTimeImmutable) {
            return ($validFrom <= $now && $now <= $validUntil) ? 'current' : 'planned';
        }

        if ($validUntil instanceof \DateTimeImmutable && $validUntil < $now) {
            return 'past';
        }

        return 'current';
    }

    private function looksLikePlannedWork(string $text): bool
    {
        return (bool) preg_match(
            '/\b(sera interrompu|sera pas desservi|seront interrompus|ne sera pas desservi|travaux de modernisation|travaux programmés|du \d{1,2} [a-zéûô]+ au \d{1,2}|à partir du \d{1,2}|week-end, le trafic)\b/iu',
            $text,
        );
    }

    private function looksLikeCurrentIncident(string $text): bool
    {
        return (bool) preg_match(
            '/\b(trafic perturbé|incident|panne|interruption|malaise voyageur|signalisation|trains stationnent|gêne|affluence|perturbé)\b/iu',
            $text,
        );
    }
}
