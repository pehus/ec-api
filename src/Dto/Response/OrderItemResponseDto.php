<?php

namespace App\Dto\Response;

class OrderItemResponseDto
{
    public function __construct(
        public string $sku,
        public string $name,
        public float $price,
        public int $quantity,
        public float $total,
    ) {
    }
}
