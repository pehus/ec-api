<?php

namespace App\Interface;

use App\Dto\Provider\LocationDto;

interface LocationProviderInterface
{
    public function geocode(string $address): ?LocationDto;
}
