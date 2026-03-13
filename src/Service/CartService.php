<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;

class CartService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartRepository $cartRepository,
    ) {
    }

    public function createCart(): Cart
    {
        $cart = new Cart();

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }

    public function getCart(int $id): ?Cart
    {
        return $this->cartRepository->find($id);
    }

    public function addProduct(int $cartId, string $sku, int $quantity = 1): ?Cart
    {
        $cart = $this->cartRepository->find($cartId);

        if (!$cart) {
            return null;
        }

        foreach ($cart->getItems() as $item) {
            if ($item->getSku() === $sku) {
                $item->setQuantity($item->getQuantity() + $quantity);
                $this->entityManager->flush();

                return $cart;
            }
        }

        $item = new CartItem();
        $item->setSku($sku);
        $item->setQuantity($quantity);
        $item->setCart($cart);

        $cart->addItem($item);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $cart;
    }

    public function removeProduct(int $cartId, string $sku, ?int $quantity = null): ?Cart
    {
        $cart = $this->cartRepository->find($cartId);

        if (!$cart) {
            return null;
        }

        foreach ($cart->getItems() as $item) {
            if ($item->getSku() !== $sku) {
                continue;
            }

            if ($quantity === null || $quantity >= $item->getQuantity()) {
                $cart->removeItem($item);
                $this->entityManager->remove($item);
            } else {
                $item->setQuantity($item->getQuantity() - $quantity);
            }

            $this->entityManager->flush();

            return $cart;
        }

        return $cart;
    }
}
