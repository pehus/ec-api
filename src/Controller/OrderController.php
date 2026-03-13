<?php

namespace App\Controller;

use App\Dto\Request\CreateOrderRequestDto;
use App\Dto\Response\OrderItemResponseDto;
use App\Dto\Response\OrderResponseDto;
use App\Entity\Order;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'order_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var CreateOrderRequestDto $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CreateOrderRequestDto::class,
            'json'
        );

        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 400);
        }

        $order = $this->orderService->createOrderFromCart(
            $dto->cartId,
            $dto->shippingAddress
        );

        if (!$order) {
            return $this->json(['error' => 'Cart not found or empty'], 400);
        }

        return $this->json($this->normalizeOrder($order));
    }

    #[Route('', name: 'order_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $orders = $this->orderService->getOrders();

        $data = array_map(
            fn (Order $order) => $this->normalizeOrder($order),
            $orders
        );

        return $this->json([
            'orders' => $data,
        ]);
    }

    #[Route('/{id}', name: 'order_detail', methods: ['GET'])]
    public function detail(int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id);

        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        return $this->json($this->normalizeOrder($order));
    }

    private function normalizeOrder(Order $order): array
    {
        $items = [];
        $total = 0.0;

        foreach ($order->getItems() as $item) {
            $price = 100.0;
            $lineTotal = $price * $item->getQuantity();

            $items[] = new OrderItemResponseDto(
                sku: $item->getSku(),
                name: 'Product ' . $item->getSku(),
                price: $price,
                quantity: $item->getQuantity(),
                total: $lineTotal,
            );

            $total += $lineTotal;
        }

        $geoLocation = null;
        if ($order->getGeoLat() !== null && $order->getGeoLng() !== null) {
            $geoLocation = $order->getGeoLat() . ',' . $order->getGeoLng();
        }

        $dto = new OrderResponseDto(
            id: (string) $order->getId(),
            createdAt: $order->getCreatedAt()->format(DATE_ATOM),
            items: $items,
            total: $total,
            shippingAddress: $order->getShippingAddress(),
            geoLocation: $geoLocation,
        );

        return $this->serializer->normalize($dto);
    }
}
