<?php

namespace App\Dto\Response;

class OrderResponseDto
{
    /**
     * @param OrderItemResponseDto[] $items
     */
    public function __construct(
        public string $id,
        public string $createdAt,
        public array $items,
        public float $total,
        public string $shippingAddress,
        public ?string $geoLocation = null,
    ) {
    }
}
