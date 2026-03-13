<?php

class OrderManager
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private Mailer $mailer,
        private ProductRepository $productRepository
    ) {}

    public function processOrder(array $orderData): bool
    {
        $this->validateOrderData($orderData);

        $customer = $this->getOrCreateCustomer($orderData);

        $items = [];
        $total = 0;

        foreach ($orderData['items'] as $item) {
            $product = $this->productRepository->findBySku($item['sku']);

            if ($product === null) {
                throw new RuntimeException("Product {$item['sku']} not found");
            }

            $lineTotal = $product->price * $item['quantity'];

            $items[] = [
                'sku' => $product->sku,
                'price' => $product->price,
                'quantity' => $item['quantity'],
                'total' => $lineTotal,
            ];

            $total += $lineTotal;
        }

        $order = [
            'customer_id' => $customer->id,
            'items' => $items,
            'total' => $total,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->storeOrder($order);

        $this->sendConfirmation($customer, $total);

        return true;
    }

    private function validateOrderData(array $data): void
    {
        if (!isset($data['email'], $data['name'], $data['address'], $data['items'])) {
            throw new InvalidArgumentException('Invalid order data');
        }

        if (!is_array($data['items']) || empty($data['items'])) {
            throw new InvalidArgumentException('Order must contain items');
        }
    }

    private function getOrCreateCustomer(array $orderData): Customer
    {
        $customer = $this->customerRepository->findByEmail($orderData['email']);

        if ($customer !== null) {
            return $customer;
        }

        $customer = new Customer();
        $customer->name = $orderData['name'];
        $customer->email = $orderData['email'];
        $customer->address = $orderData['address'];

        $this->customerRepository->save($customer);

        return $customer;
    }

    private function storeOrder(array $order): void
    {
        file_put_contents(
            'orders.json',
            json_encode($order) . PHP_EOL,
            FILE_APPEND
        );
    }

    private function sendConfirmation(Customer $customer, float $total): void
    {
        $message = sprintf(
            "Thank you for your order!\n\nTotal: %s\n\nWe will deliver to: %s",
            $total,
            $customer->address
        );

        $this->mailer->send(
            $customer->email,
            'Order confirmation',
            $message
        );
    }
}
