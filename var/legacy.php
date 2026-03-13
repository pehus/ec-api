<?php

class OrderManager
{
    public function __construct(
        private CustomerRepository $customerRepository = new CustomerRepository(),
        private Mailer $mailer = new Mailer(),
    ) {
        // Poznámka:
        // V dalším kroku bych dependency předával vždy zvenku
        // a nepoužíval defaultní new přímo zde.
    }

    public function processOrder(array $orderData): bool
    {
        // Poznámka:
        // Tady by bylo vhodné přidat DTO nebo samostatnou validaci vstupu,
        // aby se nepracovalo přímo s nevalidovaným polem.
        $email = $orderData['email'] ?? null;
        $name = $orderData['name'] ?? null;
        $address = $orderData['address'] ?? null;
        $items = $orderData['items'] ?? null;

        if (!is_string($email) || $email === '') {
            throw new InvalidArgumentException('Missing email.');
        }

        if (!is_string($name) || $name === '') {
            throw new InvalidArgumentException('Missing name.');
        }

        if (!is_string($address) || $address === '') {
            throw new InvalidArgumentException('Missing address.');
        }

        if (!is_array($items) || count($items) === 0) {
            throw new InvalidArgumentException('Order items are missing.');
        }

        $order = [];

        $customer = $this->customerRepository->findByEmail($email);
        if ($customer === null) {
            $customer = new Customer();
            $customer->name = $name;
            $customer->email = $email;
            $customer->address = $address;

            $this->customerRepository->save($customer);
        }

        $order['customer_id'] = $customer->id;
        $order['items'] = [];

        $total = 0.0;

        foreach ($items as $item) {
            $sku = $item['sku'] ?? null;
            $quantity = $item['quantity'] ?? null;

            if (!is_string($sku) || $sku === '') {
                throw new InvalidArgumentException('Item sku is missing.');
            }

            if (!is_int($quantity) || $quantity <= 0) {
                throw new InvalidArgumentException('Item quantity must be positive integer.');
            }

            $product = $this->findBySku($sku);

            // Poznámka:
            // V původním kódu se chybějící produkt ignoroval.
            // To by mohlo vést k tiché ztrátě položky v objednávce.
            // Proto zde raději vyhazuji výjimku.
            if ($product === null) {
                throw new RuntimeException(sprintf('Product with sku "%s" was not found.', $sku));
            }

            $line = [];
            $line['sku'] = $product->sku;
            $line['price'] = $product->price;
            $line['quantity'] = $quantity;
            $line['total'] = $product->price * $quantity;

            $order['items'][] = $line;
            $total += $line['total'];
        }

        $order['total'] = $total;
        $order['created_at'] = date('Y-m-d H:i:s');

        $this->appendOrder($order);

        // Poznámka:
        // V původním kódu byly proměnné v apostrofech,
        // takže se vůbec nevyhodnotily.
        $message = sprintf(
            "Thank you for your order!\n\nTotal: %s\n\nWe will deliver to: %s",
            $total,
            $customer->address
        );

        $this->mailer->send($customer->email, 'Order confirmation', $message);

        return true;
    }

    private function findBySku(string $sku): ?stdClass
    {
        $content = file_get_contents('products.json');

        if ($content === false) {
            throw new RuntimeException('Unable to read products.json');
        }

        $products = json_decode($content, true);

        if (!is_array($products)) {
            throw new RuntimeException('Invalid products.json content');
        }

        foreach ($products as $p) {
            $productSku = $p['sku'] ?? null;
            $productPrice = $p['price'] ?? null;

            if (!is_string($productSku)) {
                continue;
            }

            if (!is_numeric($productPrice)) {
                continue;
            }

            if ($productSku === $sku) {
                $product = new stdClass();
                $product->sku = $productSku;
                $product->price = (float) $productPrice;

                return $product;
            }
        }

        return null;
    }

    private function appendOrder(array $order): void
    {
        $encodedOrder = json_encode($order);

        if (!is_string($encodedOrder)) {
            throw new RuntimeException('Unable to encode order.');
        }

        file_put_contents('orders.json', $encodedOrder . PHP_EOL, FILE_APPEND);

        // Poznámka:
        // V dalším kroku by bylo lepší oddělit ukládání objednávky
        // do samostatné repository nebo storage třídy.
    }
}

class CustomerRepository
{
    public function findByEmail(string $email): ?stdClass
    {
        $content = file_get_contents('customers.json');

        if ($content === false) {
            throw new RuntimeException('Unable to read customers.json');
        }

        $customers = json_decode($content, true);

        if (!is_array($customers)) {
            throw new RuntimeException('Invalid customers.json content');
        }

        foreach ($customers as $c) {
            $customerEmail = $c['email'] ?? null;
            $customerId = $c['id'] ?? null;
            $customerName = $c['name'] ?? null;
            $customerAddress = $c['address'] ?? null;

            if (!is_string($customerEmail)) {
                continue;
            }

            if ($customerEmail === $email) {
                $customer = new stdClass();
                $customer->id = $customerId;
                $customer->name = $customerName;
                $customer->email = $customerEmail;
                $customer->address = $customerAddress;

                return $customer;
            }
        }

        return null;
    }

    public function save(Customer $customer): void
    {
        $content = file_get_contents('customers.json');

        if ($content === false) {
            throw new RuntimeException('Unable to read customers.json');
        }

        $customers = json_decode($content, true);

        if (!is_array($customers)) {
            throw new RuntimeException('Invalid customers.json content');
        }

        $customer->id = count($customers) + 1;

        $customers[] = [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'address' => $customer->address,
        ];

        $encodedCustomers = json_encode($customers);

        if (!is_string($encodedCustomers)) {
            throw new RuntimeException('Unable to encode customers.');
        }

        file_put_contents('customers.json', $encodedCustomers);

        // Poznámka:
        // Generování ID přes count() + 1 není ideální.
        // V reálné aplikaci by to řešila databáze nebo persistentní storage.
    }
}

class Mailer
{
    public function send(string $to, string $subject, string $message): void
    {
        file_put_contents(
            'emails.log',
            '[' . date('Y-m-d H:i:s') . "] To: $to\nSubject: $subject\n$message\n\n",
            FILE_APPEND
        );

        // Poznámka:
        // V dalším kroku by bylo vhodné použít interface
        // a oddělit skutečný mailer od této jednoduché implementace.
    }
}

class Customer
{
    public ?int $id = null;
    public string $name;
    public string $email;
    public string $address;
}
