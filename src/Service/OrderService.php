<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Interface\LocationProviderInterface;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly CartService $cartService,
        private readonly LocationProviderInterface $locationProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function createOrderFromCart(int $cartId, string $shippingAddress): ?Order
    {
        $cart = $this->cartService->getCart($cartId);

        if ($cart === null || $cart->getItems()->isEmpty()) {
            return null;
        }

        $order = new Order();
        $order->setShippingAddress($shippingAddress);

        $this->fillGeoLocation($order, $shippingAddress);

        foreach ($cart->getItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setSku($cartItem->getSku());
            $orderItem->setQuantity($cartItem->getQuantity());

            $order->addItem($orderItem);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * @return Order[]
     */
    public function getOrders(): array
    {
        return $this->orderRepository->findBy([], ['id' => 'DESC']);
    }

    public function getOrder(int $id): ?Order
    {
        return $this->orderRepository->find($id);
    }

    private function fillGeoLocation(Order $order, string $shippingAddress): void
    {
        try {
            $coords = $this->locationProvider->geocode($shippingAddress);

            if ($coords === null) {
                return;
            }

            $order->setGeoLat($coords->lat);
            $order->setGeoLng($coords->lng);
        } catch (\Throwable $t) {
            $this->logger->warning(
                'Geo location lookup failed',
                [
                    'address' => $shippingAddress,
                    'exception' => $t->getMessage(),
                ]
            );
        }
    }
}
