<?php

namespace App\Provider;

use App\Dto\Provider\LocationDto;
use App\Interface\LocationProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenStreetMapLocationProvider implements LocationProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function geocode(string $address): ?LocationDto
    {
        $response = $this->httpClient->request(
            'GET',
            '/search',
            [
                'query' => [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                ],
            ]
        );

        $data = $response->toArray(false);

        $result = $data[0] ?? null;

        if (!$result) {
            return null;
        }

        if (!array_key_exists('lat', $result) || !array_key_exists('lon', $result)) {
            throw new \RuntimeException('Invalid response from OpenStreetMap geocoding API.');
        }

        return new LocationDto(
            lat: (float) $data[0]['lat'],
            lng: (float) $data[0]['lon'],
        );
    }
}
