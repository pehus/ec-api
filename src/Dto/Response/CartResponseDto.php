<?php

namespace App\Dto\Response;

class CartResponseDto
{
    /**
     * @param CartItemResponseDto[] $items
     */
    public function __construct(
        public string $id,
        public array $items,
        public int $itemCount,
        public int $totalQuantity,
        public float $total,
    ) {
    }
}
