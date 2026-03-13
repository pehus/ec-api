<?php

namespace App\Dto\Response;

class CartItemResponseDto
{
    public function __construct(
        public ProductResponseDto $product,
        public int $quantity,
        public float $total,
    ) {
    }
}
