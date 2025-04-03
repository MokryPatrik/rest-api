# REST API Project

A simple REST API built with PHP for product management.

## Prerequisites

- Docker
- Docker Compose

## Getting Started

### 1. Clone the repository

```bash
git clone <repository-url>
cd rest-api
```

### 2. Configure environment variables

Create a `.env` file in the root directory with the following variables:

```
DB_HOST=mysql
DB_PORT=3306
DB_NAME=product_management
DB_USER=root
DB_PASSWORD=root
APP_BEARER_TOKEN=test-1234
```

### 3. Start the application

```bash
docker-compose up -d --build
```

This will start the following services:
- PHP application server
- Nginx web server
- MySQL database

### 4. Access the application

The API will be available at:
```
http://localhost:8080
```

### 5. Run database migrations

```bash
docker-compose exec php php migrations/001_create_products_table.php
```

### 6. Run tests

```bash
docker-compose exec php ./vendor/bin/phpunit
```

## API Endpoints

- `GET /api/products` - Get all products
- `GET /api/products/{id}` - Get a specific product
- `POST /api/products` - Create a new product
- `PUT /api/products/{id}` - Update a product
- `DELETE /api/products/{id}` - Delete a product
- `GET /api/products/nearest-price` - Find the product with the nearest price

## Authentication

The API uses Bearer token authentication. Include the token in the `Authorization` header:

```
Authorization: Bearer your_bearer_token
```