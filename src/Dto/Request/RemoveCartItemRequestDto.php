<?php

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RemoveCartItemRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $cartId;

    #[Assert\NotBlank]
    public string $sku;

    #[Assert\Positive]
    public ?int $quantity = null;
}
