
# EC API

Simple REST API for managing carts and orders built with **Symfony**.

The application allows creating carts, adding/removing items, and creating orders from carts.  
When an order is created, the shipping address is geocoded using the **OpenStreetMap Nominatim API**.

---

# Requirements

- Docker
- Docker Compose
- PHP 8.4
- Composer

---

# Installation

Clone the repository:

```bash
git clone <repository>
cd project/docker
```

Start the application:

```bash
docker compose up -d --build
```

The API will be available at:

```
http://localhost:8000
```

---

# Database

The application uses **MariaDB**.

Two databases are created:

- `ec_api` – development database
- `ec_api_test` – database used for automated tests

The test database is automatically created during container initialization via SQL script.

Run migrations:

```bash
php bin/console doctrine:migrations:migrate
```

---

# Running Tests

Tests use the dedicated **test database**.

```bash
php bin/phpunit
```

The test database schema is created automatically via migrations.

---

# API Endpoints

## Create Cart

```bash
POST /api/cart
```

Response example:

```json
{
  "id": "1",
  "items": [],
  "item_count": 0,
  "total_quantity": 0,
  "total": 0
}
```

---

## Add Item to Cart

```bash
POST /api/cart/add
```

Request:

```json
{
  "cart_id": 1,
  "sku": "ABC123",
  "quantity": 2
}
```

---

## Remove Item from Cart

```bash
POST /api/cart/remove
```

Request:

```json
{
  "cart_id": 1,
  "sku": "ABC123",
  "quantity": 1
}
```

---

## Cart Detail

```bash
GET /api/cart/{id}
```

---

## Create Order

```bash
POST /api/orders
```

Request:

```json
{
  "cart_id": 1,
  "shipping_address": "Ostrožská Nová Ves, Družstevní 826"
}
```

The shipping address is geocoded using the OpenStreetMap Nominatim API.

---

## List Orders

```bash
GET /api/orders
```

---

## Order Detail

```bash
GET /api/orders/{id}
```

---

# Architecture

The project follows a simple layered structure:

```
Controller
Service
Provider
DTO
Entity
Repository
```

### Controllers
Handle HTTP requests and responses.

### Services
Contain business logic for carts and orders.

### Providers
External integrations (e.g. geolocation provider).

### DTO
Used for request and response data transfer.

### Entities
Doctrine ORM entities mapped to database tables.

---

# External API Integration

The application integrates with the **OpenStreetMap Nominatim API** for geocoding shipping addresses.

Implementation details:

- `LocationProviderInterface`
- `OpenStreetMapLocationProvider`
- `LocationDto`

Errors from the external API are handled and logged without breaking order creation.

---

# Testing Strategy

Functional API tests were implemented using Symfony's `WebTestCase`.

Covered scenarios:

- cart creation
- adding items to cart
- order creation from cart

Tests run against a dedicated database to avoid affecting development data.

---

# Legacy Refactoring

The repository contains a separate `legacy.php` file.

The goal was to:

- remove bugs
- improve readability
- simplify logic
- apply modern PHP practices

This refactoring is intentionally isolated from the main application.

---

# Possible Improvements

If the project were extended further, the following improvements would be implemented:

### Product Catalog
Currently products are mocked in responses.  
A real product catalog with pricing would be introduced.

### Validation
DTO validation using Symfony Validator.

### DTO Mapping
Introduce dedicated mappers to separate entity → response transformation.

### Pagination
Order listing endpoint could support pagination.

### Caching
Geolocation requests could be cached to reduce external API calls.

### OpenAPI Documentation
Generate API documentation using Swagger/OpenAPI.

---

# Design Decisions

Key priorities for this implementation:

- simplicity
- clear architecture
- separation of concerns
- testability

The goal was to keep the solution easy to understand while demonstrating proper Symfony practices.

---

# Author

EC API implementation task.
