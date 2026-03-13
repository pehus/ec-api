<?php

namespace App\Dto\Provider;

class LocationDto
{
    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
    ) {
    }
}
