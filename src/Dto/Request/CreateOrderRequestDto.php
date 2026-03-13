<?php

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateOrderRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $cartId;

    #[Assert\NotBlank]
    public string $shippingAddress;
}
