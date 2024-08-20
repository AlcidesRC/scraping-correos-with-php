<?php

declare(strict_types=1);

namespace App\Importers;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use RuntimeException;

final class Correos
{
    private const CORREOS_BASE_URL = 'https://api1.correos.es';
    private const CORREOS_ENDPOINT_URI = '/digital-services/searchengines/api/v1/suggestions?text={POSTALCODE}';
    private const PROVINCES_START = 1;
    private const PROVINCES_END = 52;
    private const RANGE_START = 0;
    private const RANGE_END = 999;
    private const CHUNK_SIZE = 50;

    public static function getExceptionMessage(int $provinceId): string
    {
        return strtr('Province [ {PROVINCE} ] must be in range {MIN}..{MAX}', [
            '{PROVINCE}' => $provinceId,
            '{MIN}' => self::PROVINCES_START,
            '{MAX}' => self::PROVINCES_END,
        ]);
    }

    /**
     * @return array<int, mixed>
     * @throws \Throwable
     */
    public function getProvincePostalCodes(int $provinceId): array
    {
        if ($provinceId < self::PROVINCES_START || $provinceId > self::PROVINCES_END) {
            throw new RuntimeException(self::getExceptionMessage($provinceId));
        }

        $client = new Client([
            'base_uri' => self::CORREOS_BASE_URL,
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);

        $chunks = array_chunk(
            range(self::RANGE_START, self::RANGE_END),
            self::CHUNK_SIZE
        );

        $result = [];

        foreach ($chunks as $chunk) {
            $promises = array_map(function (int $rangeId) use ($client, $provinceId) {
                return $client->getAsync($this->generateUri($provinceId, $rangeId));
            }, $chunk);

            $promiseResponses = Promise\Utils::unwrap($promises);

            foreach ($promiseResponses as $response) {
                $json = json_decode($response->getBody()->getContents(), true);

                if (!isset($json['suggestions'])) {
                    continue;
                }

                $suggestions = array_map(static function (array $suggestion) {
                    $components = explode(', ', $suggestion['text']);

                    $country = array_pop($components);
                    $region = array_pop($components);
                    $province = array_pop($components);
                    $postal = array_shift($components);
                    $city = implode('. ', $components);

                    return [
                        $country,
                        $postal,
                        $city,
                        $province,
                        $region,
                        $suggestion['latitude'],
                        $suggestion['longitude']
                    ];
                }, $json['suggestions']);

                $result = array_merge($result, $suggestions);
            }
        }

        return $result;
    }

    private function generateUri(int $province, int $range): string
    {
        return strtr(self::CORREOS_ENDPOINT_URI, [
            '{POSTALCODE}' => $this->generatePostalCode($province, $range),
        ]);
    }

    private function generatePostalCode(int $province, int $range): string
    {
        $province = str_pad((string)$province, 2, '0', STR_PAD_LEFT);
        $range = str_pad((string)$range, 3, '0', STR_PAD_LEFT);

        return $province . $range;
    }
}
