<?php

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

class AddCartItemRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $cartId;

    #[Assert\NotBlank]
    public string $sku;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $quantity = 1;
}
