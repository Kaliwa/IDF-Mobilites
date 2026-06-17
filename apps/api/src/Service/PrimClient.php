<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PrimClient
{
    private const BASE_URL = 'https://prim.iledefrance-mobilites.fr/marketplace';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $primApiToken,
    ) {
    }

    /**
     * Fetch active Perturbation messages for a given STIF line reference.
     *
     * @return list<array{id:string,text:string,validUntil:\DateTimeImmutable|null,recordedAt:\DateTimeImmutable}>
     */
    public function getDisruptions(string $primRef): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/general-message', [
                'headers' => ['apikey' => $this->primApiToken],
                'query' => ['LineRef' => $primRef],
                'timeout' => 8.0,
            ]);

            $data = $response->toArray();
        } catch (\Throwable) {
            return [];
        }

        $messages = $data['Siri']['ServiceDelivery']['GeneralMessageDelivery'][0]['InfoMessage'] ?? [];
        $disruptions = [];

        foreach ($messages as $message) {
            if ('Perturbation' !== ($message['InfoChannelRef']['value'] ?? '')) {
                continue;
            }

            $id = (string) ($message['ItemIdentifier'] ?? '');
            if ('' === $id) {
                continue;
            }

            $text = $this->extractMessage($message['Content']['Message'] ?? [], 'SHORT_MESSAGE');
            if ('' === $text) {
                $text = $this->extractMessage($message['Content']['Message'] ?? [], 'LONG_MESSAGE');
            }
            if ('' === $text) {
                continue;
            }

            $detail = $this->extractMessage($message['Content']['Message'] ?? [], 'LONG_MESSAGE');

            $disruptions[] = [
                'id' => $id,
                'text' => $text,
                'detail' => $detail !== '' && $detail !== $text ? $detail : null,
                'validUntil' => isset($message['ValidUntilTime'])
                    ? new \DateTimeImmutable((string) $message['ValidUntilTime'])
                    : null,
                'recordedAt' => new \DateTimeImmutable((string) ($message['RecordedAtTime'] ?? 'now')),
            ];
        }

        return $disruptions;
    }

    /**
     * @param list<array{MessageType:string,MessageText:array{value:string}}> $messages
     */
    private function extractMessage(array $messages, string $type): string
    {
        foreach ($messages as $msg) {
            if ($type === ($msg['MessageType'] ?? '')) {
                return (string) ($msg['MessageText']['value'] ?? '');
            }
        }

        return '';
    }
}
