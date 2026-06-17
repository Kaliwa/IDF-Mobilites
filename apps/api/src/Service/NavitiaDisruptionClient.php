<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class NavitiaDisruptionClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey = null,
        private readonly ?string $navitiaBaseUrl = null,
    ) {
    }

    /**
     * @return list<array{
     *   id:string,
     *   text:string,
     *   detail:?string,
     *   status:string,
     *   effect:?string,
     *   severity:?string,
     *   cause:?string,
     *   category:?string,
     *   updatedAt:\DateTimeImmutable,
     *   validFrom:? \DateTimeImmutable,
     *   validUntil:? \DateTimeImmutable
     * }>
     */
    public function getForLine(string $lineId): array
    {
        if (!$this->apiKey || !$this->navitiaBaseUrl || $lineId === '') {
            return [];
        }

        $url = sprintf(
            '%s/lines/%s/disruptions?count=10&depth=1',
            rtrim($this->navitiaBaseUrl, '/'),
            rawurlencode($lineId),
        );

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'apikey' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'timeout' => 8.0,
            ]);
            $data = $response->toArray(false);
        } catch (\Throwable) {
            return [];
        }

        $items = [];
        foreach ($data['disruptions'] ?? [] as $disruption) {
            if (!is_array($disruption)) {
                continue;
            }

            $messages = $this->extractMessages($disruption);
            if ($messages['text'] === '') {
                continue;
            }

            $period = $this->extractPeriod($disruption['application_periods'] ?? []);

            $items[] = [
                'id' => (string) ($disruption['id'] ?? $disruption['disruption_id'] ?? uniqid('navitia-', true)),
                'text' => $messages['text'],
                'detail' => $messages['detail'],
                'status' => (string) ($disruption['status'] ?? ''),
                'effect' => isset($disruption['severity']['effect'])
                    ? (string) $disruption['severity']['effect']
                    : null,
                'severity' => isset($disruption['severity']['name'])
                    ? (string) $disruption['severity']['name']
                    : null,
                'cause' => isset($disruption['cause']) ? (string) $disruption['cause'] : null,
                'category' => isset($disruption['category']) ? (string) $disruption['category'] : null,
                'updatedAt' => $this->parseNavitiaDate((string) ($disruption['updated_at'] ?? 'now')),
                'validFrom' => $period['begin'],
                'validUntil' => $period['end'],
            ];
        }

        return $items;
    }

    /**
     * @param array<string,mixed> $disruption
     *
     * @return array{text:string,detail:?string}
     */
    private function extractMessages(array $disruption): array
    {
        $title = '';
        $detail = null;

        foreach ($disruption['messages'] ?? [] as $message) {
            if (!is_array($message)) {
                continue;
            }

            $text = trim(strip_tags((string) ($message['text'] ?? '')));
            if ($text === '') {
                continue;
            }

            $channelTypes = $message['channel']['types'] ?? [];
            if (in_array('title', $channelTypes, true) || in_array('notification', $channelTypes, true)) {
                $title = $title !== '' ? $title : $text;
            }
            if (in_array('web', $channelTypes, true)) {
                $detail = $text;
            }
        }

        if ($title === '' && isset($disruption['messages'][0]['text'])) {
            $title = trim(strip_tags((string) $disruption['messages'][0]['text']));
        }

        return ['text' => $title, 'detail' => $detail];
    }

    /**
     * @param list<array<string,mixed>> $periods
     *
     * @return array{begin:?\DateTimeImmutable,end:?\DateTimeImmutable}
     */
    private function extractPeriod(array $periods): array
    {
        $begin = null;
        $end = null;

        foreach ($periods as $period) {
            if (!is_array($period)) {
                continue;
            }

            if (isset($period['begin'])) {
                $parsedBegin = $this->parseNavitiaDate((string) $period['begin']);
                $begin = $begin === null || $parsedBegin < $begin ? $parsedBegin : $begin;
            }

            if (isset($period['end'])) {
                $parsedEnd = $this->parseNavitiaDate((string) $period['end']);
                $end = $end === null || $parsedEnd > $end ? $parsedEnd : $end;
            }
        }

        return ['begin' => $begin, 'end' => $end];
    }

    private function parseNavitiaDate(string $value): \DateTimeImmutable
    {
        $formats = [
            'Ymd\THis',
            \DateTimeInterface::ATOM,
            'Y-m-d H:i:s',
        ];

        foreach ($formats as $format) {
            $parsed = \DateTimeImmutable::createFromFormat($format, $value);
            if ($parsed instanceof \DateTimeImmutable) {
                return $parsed;
            }
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return new \DateTimeImmutable();
        }
    }
}
