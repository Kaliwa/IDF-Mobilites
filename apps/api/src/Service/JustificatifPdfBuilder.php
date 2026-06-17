<?php

namespace App\Service;

use App\Entity\Journey;
use App\Entity\User;

class JustificatifPdfBuilder
{
    public function __construct(private readonly string $issuerName = 'Île-de-France Mobilités')
    {
    }

    /**
     * Build a minimal PDF without external libraries.
     */
    public function build(User $user, Journey $journey, array $disruption): string
    {
        $title = 'Attestation de perturbation';
        $ref = ($journey->getId() ?? 'N/A') . '-' . (new \DateTimeImmutable())->format('Ymd_His');
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $issuedAt = $now->format('d/m/Y') . ' à ' . $now->format('H:i');

        $detail = (string) ($disruption['detail'] ?? $disruption['message'] ?? 'Perturbation confirmée');
        $detailLines = $this->wrapText($detail, 82);

        $bodyLines = [
            'Émetteur : ' . $this->issuerName,
            'Référence : ' . $ref,
            'Utilisateur : ' . ($user->getEmail() ?? 'Compte'),
            'Trajet : ' . $journey->getOriginName() . ' -> ' . $journey->getDestinationName(),
            'Ligne impactée : ' . ($disruption['lineName'] ?? $disruption['line'] ?? 'N/A'),
            'Nature : ' . ($disruption['cause'] ?? 'incident'),
            'Statut au moment de la demande : ' . ($disruption['status'] ?? 'Incident en cours'),
            'Détail : ' . array_shift($detailLines),
            ...array_map(static fn (string $line): string => '  ' . $line, $detailLines),
            'Début incident : ' . $this->formatFrenchDateTime(
                isset($disruption['validFrom']) ? (string) $disruption['validFrom'] : null,
                isset($disruption['updatedAt']) ? (string) $disruption['updatedAt'] : null,
            ),
            'Attestation délivrée le : ' . $issuedAt,
        ];

        $content = $this->buildContent($title, $bodyLines);
        $contentLength = strlen($content);

        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
        $pdf .= "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
        $pdf .= "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n";
        $pdf .= "4 0 obj << /Length $contentLength >> stream\n";
        $pdf .= $content . "\nendstream endobj\n";
        $pdf .= "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >> endobj\n";
        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "1 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "2 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "3 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "4 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "5 0 obj"));
        $pdf .= "trailer << /Size 6 /Root 1 0 R >>\nstartxref\n";
        $pdf .= $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    /**
     * @param string[] $lines
     */
    private function buildContent(string $title, array $lines): string
    {
        $yStart = 780;
        $lineHeight = 16;

        $content = "BT\n";
        $content .= "/F1 20 Tf\n50 $yStart Td\n(" . $this->encodePdfText($title) . ") Tj\n";
        $content .= "/F1 12 Tf\n0 -18 Td\n(" . $this->encodePdfText($this->issuerName . ' - Attestation officielle') . ") Tj\n";
        $content .= "0 -12 Td\n(" . $this->encodePdfText(str_repeat('=', 72)) . ") Tj\n";
        $content .= "0 -" . ($lineHeight + 6) . " Td\n";

        foreach ($lines as $line) {
            $content .= "(" . $this->encodePdfText($line) . ") Tj\n";
            $content .= "0 -" . $lineHeight . " Td\n";
        }

        $content .= "0 -6 Td\n(" . $this->encodePdfText(str_repeat('=', 72)) . ") Tj\n";
        $content .= "0 -" . $lineHeight . " Td\n";
        $content .= "(" . $this->encodePdfText('Incident constaté en temps réel au moment de la demande.') . ") Tj\n";
        $content .= "0 -" . $lineHeight . " Td\n";
        $content .= "(" . $this->encodePdfText("Elle n'engage pas la responsabilité de l'opérateur au-delà des conditions applicables.") . ") Tj\n";
        $content .= "ET";

        return $content;
    }

    private function encodePdfText(string $text): string
    {
        $text = $this->normalizeText($text);
        $encoded = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);

        if ($encoded === false || $encoded === '') {
            $encoded = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
        }

        return $this->escapePdfBytes($encoded);
    }

    private function normalizeText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(
            [
                "\u{202F}", "\u{00A0}", "\u{2009}", "\u{2007}",
                "\u{2019}", "\u{2018}", "\u{201C}", "\u{201D}",
                "\u{2013}", "\u{2014}", "\u{2026}", "\u{2192}",
                '’', '‘', '“', '”', '–', '—', '…', '→',
            ],
            [' ', ' ', ' ', ' ', "'", "'", '"', '"', '-', '-', '...', '->', "'", "'", '"', '"', '-', '-', '...', '->'],
            $text,
        );
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return string[]
     */
    private function wrapText(string $text, int $maxLength): array
    {
        $text = $this->normalizeText($text);
        if ($text === '') {
            return [''];
        }

        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if (strlen($candidate) > $maxLength && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function formatFrenchDateTime(?string $primary, ?string $fallback): string
    {
        foreach ([$primary, $fallback] as $value) {
            if ($value === null || trim($value) === '') {
                continue;
            }

            try {
                $date = (new \DateTimeImmutable($value))->setTimezone(new \DateTimeZone('Europe/Paris'));

                return $date->format('d/m/Y') . ' à ' . $date->format('H:i');
            } catch (\Throwable) {
                continue;
            }
        }

        return 'Non précisé';
    }

    private function escapePdfBytes(string $text): string
    {
        $escaped = '';
        $length = strlen($text);

        for ($i = 0; $i < $length; ++$i) {
            $char = $text[$i];
            $byte = ord($char);

            if ($char === '\\' || $char === '(' || $char === ')') {
                $escaped .= '\\' . $char;
                continue;
            }

            if ($byte < 32 || $byte > 126) {
                $escaped .= sprintf('\\%03o', $byte);
                continue;
            }

            $escaped .= $char;
        }

        return $escaped;
    }
}
