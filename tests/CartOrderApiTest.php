<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartOrderApiTest extends WebTestCase
{
    public function testCreateCart(): void
    {
        $client = static::createClient();
        $this->resetDatabase();

        $client->request('POST', '/api/cart');

        self::assertResponseStatusCodeSame(201);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('item_count', $data);
        self::assertArrayHasKey('total_quantity', $data);
        self::assertArrayHasKey('total', $data);

        self::assertSame([], $data['items']);
        self::assertSame(0, $data['item_count']);
        self::assertSame(0, $data['total_quantity']);
        self::assertSame(0, $data['total']);
    }

    public function testAddItemToCart(): void
    {
        $client = static::createClient();
        $this->resetDatabase();

        $client->request('POST', '/api/cart');
        $cart = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'POST',
            '/api/cart/add',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'cart_id' => (int) $cart['id'],
                'sku' => 'ABC123',
                'quantity' => 2,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('ABC123', $data['items'][0]['product']['sku']);
        self::assertSame(2, $data['items'][0]['quantity']);
        self::assertSame(1, $data['item_count']);
        self::assertSame(2, $data['total_quantity']);
        self::assertSame(200, $data['total']);
    }

    public function testCreateOrderFromCart(): void
    {
        $client = static::createClient();
        $this->resetDatabase();

        $client->request('POST', '/api/cart');
        $cart = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'POST',
            '/api/cart/add',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'cart_id' => (int) $cart['id'],
                'sku' => 'ABC123',
                'quantity' => 2,
            ], JSON_THROW_ON_ERROR)
        );

        $client->request(
            'POST',
            '/api/orders',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'cart_id' => (int) $cart['id'],
                'shipping_address' => 'Ostrožská Nová Ves, Družstevní 826',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertArrayHasKey('id', $data);
        self::assertSame('ABC123', $data['items'][0]['sku']);
        self::assertSame(2, $data['items'][0]['quantity']);
        self::assertSame(200, $data['total']);
        self::assertSame('Ostrožská Nová Ves, Družstevní 826', $data['shipping_address']);
    }

    private function resetDatabase(): void
    {
        self::ensureKernelShutdown();
        $kernel = self::bootKernel();
        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        $connection = $entityManager->getConnection();

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('TRUNCATE TABLE cart_item');
        $connection->executeStatement('TRUNCATE TABLE order_item');
        $connection->executeStatement('TRUNCATE TABLE `order`');
        $connection->executeStatement('TRUNCATE TABLE cart');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        $entityManager->clear();
    }
}
