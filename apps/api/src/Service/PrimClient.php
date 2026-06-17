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

            $text = $this->extractShortMessage($message['Content']['Message'] ?? []);
            if ('' === $text) {
                continue;
            }

            $disruptions[] = [
                'id' => $id,
                'text' => $text,
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
    private function extractShortMessage(array $messages): string
    {
        foreach ($messages as $msg) {
            if ('SHORT_MESSAGE' === ($msg['MessageType'] ?? '')) {
                return (string) ($msg['MessageText']['value'] ?? '');
            }
        }

        return (string) ($messages[0]['MessageText']['value'] ?? '');
    }
}
