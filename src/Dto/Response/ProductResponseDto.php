<?php

namespace App\Dto\Response;

class ProductResponseDto
{
    public function __construct(
        public string $sku,
        public string $name,
        public float $price,
        public ?string $description = null,
    ) {
    }
}
