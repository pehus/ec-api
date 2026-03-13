<?php

namespace App\Controller;

use App\Dto\Request\AddCartItemRequestDto;
use App\Dto\Request\RemoveCartItemRequestDto;
use App\Dto\Response\CartItemResponseDto;
use App\Dto\Response\CartResponseDto;
use App\Dto\Response\ProductResponseDto;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/cart', name: 'cart_create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        $cart = $this->cartService->createCart();

        return $this->json($this->normalizeCart($cart), 201);
    }

    #[Route('/cart/{id}', name: 'cart_detail', methods: ['GET'])]
    public function detail(int $id): JsonResponse
    {
        $cart = $this->cartService->getCart($id);

        if (!$cart) {
            return $this->json(['error' => 'Cart not found'], 404);
        }

        return $this->json($this->normalizeCart($cart));
    }

    #[Route('/cart/add', name: 'cart_add_item', methods: ['POST'])]
    public function addItem(Request $request): JsonResponse
    {
        /** @var AddCartItemRequestDto $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            AddCartItemRequestDto::class,
            'json'
        );

        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 400);
        }

        $cart = $this->cartService->addProduct(
            $dto->cartId,
            $dto->sku,
            $dto->quantity
        );

        if (!$cart) {
            return $this->json(['error' => 'Cart not found'], 404);
        }

        return $this->json($this->normalizeCart($cart));
    }

    #[Route('/cart/remove', name: 'cart_remove_item', methods: ['POST'])]
    public function removeItem(Request $request): JsonResponse
    {
        /** @var RemoveCartItemRequestDto $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            RemoveCartItemRequestDto::class,
            'json'
        );

        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 400);
        }

        $cart = $this->cartService->removeProduct(
            $dto->cartId,
            $dto->sku,
            $dto->quantity
        );

        if (!$cart) {
            return $this->json(['error' => 'Cart not found'], 404);
        }

        return $this->json($this->normalizeCart($cart));
    }

    private function normalizeCart(object $cart): array
    {
        $items = [];
        $itemCount = 0;
        $totalQuantity = 0;
        $total = 0.0;

        foreach ($cart->getItems() as $item) {
            $price = 100.0;
            $lineTotal = $price * $item->getQuantity();

            $items[] = new CartItemResponseDto(
                product: new ProductResponseDto(
                    sku: $item->getSku(),
                    name: 'Product ' . $item->getSku(),
                    price: $price,
                    description: null,
                ),
                quantity: $item->getQuantity(),
                total: $lineTotal,
            );

            $itemCount++;
            $totalQuantity += $item->getQuantity();
            $total += $lineTotal;
        }

        $dto = new CartResponseDto(
            id: (string) $cart->getId(),
            items: $items,
            itemCount: $itemCount,
            totalQuantity: $totalQuantity,
            total: $total,
        );

        return $this->serializer->normalize($dto);
    }
}
