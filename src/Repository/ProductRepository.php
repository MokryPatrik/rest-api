<?php

namespace App\Repository;

use App\Entity\Product;
use App\Exception\DatabaseException;
use App\Exception\ProductNotFoundException;
use App\Exception\UniqueConstraintException;
use App\Service\DatabaseService;
use App\Service\HydrationTrait;
use PDO;
use PDOException;

class ProductRepository implements ProductRepositoryInterface
{
    use HydrationTrait;

    private PDO $pdo;

    public function __construct(
        private readonly DatabaseService $databaseService
    ) {
        $this->pdo = $databaseService->getConnection();
    }

    public function create(Product $product): int
    {
        try {
            $sql = "INSERT INTO products (name, description, brand, category, price)
                    VALUES (:name, :description, :brand, :category, :price)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'brand' => $product->getBrand(),
                'category' => $product->getCategory(),
                'price' => $product->getPrice()
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new UniqueConstraintException('Product with this name already exists.');
            }

            throw new DatabaseException('Failed to create product: ' . $e->getMessage());
        }
    }

    public function update(Product $product): bool
    {
        try {
            if ($product->getId() === null) {
                throw new ProductNotFoundException("Cannot update product without ID");
            }

            // Check if the product exists
            $existingProduct = $this->findById($product->getId());
            if ($existingProduct === null) {
                throw new ProductNotFoundException("Product with ID {$product->getId()} not found.");
            }

            $sql = "UPDATE products 
                    SET name = :name,
                        description = :description,
                        brand = :brand,
                        category = :category,
                        price = :price
                    WHERE id = :id"
            ;
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'brand' => $product->getBrand(),
                'category' => $product->getCategory(),
                'price' => $product->getPrice(),
            ]);

            if (!$result) {
                throw new ProductNotFoundException("Product with ID {$product->getId()} not found.");
            }
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new UniqueConstraintException('Product with this name already exists.');
            }

            throw new DatabaseException('Failed to update product: ' . $e->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM products WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute(['id' => $id]);

            $affectedRows = $stmt->rowCount();

            if (!$result || $affectedRows === 0) {
                throw new ProductNotFoundException("Product with ID {$id} not found");
            }
            return true;
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to delete product: ' . $e->getMessage());
        }
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $where = [];
            $params = [];

            if (!empty($filters['brand'])) {
                $where[] = "brand = :brand";
                $params['brand'] = $filters['brand'];
            }

            if (!empty($filters['category'])) {
                $where[] = "category = :category";
                $params['category'] = $filters['category'];
            }

            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            $sql = "SELECT * FROM products {$whereClause} ORDER BY id DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            
            $params['limit'] = $perPage;
            $params['offset'] = $offset;
            
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(function($row) {
                return $this->hydrateEntity($row, new Product());
            }, $rows);
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to get products: ' . $e->getMessage());
        }
    }

    public function findById(int $id): ?Product
    {
        try {
            $sql = "SELECT * FROM products WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->hydrateEntity($row, new Product()) : null;
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to find product: ' . $e->getMessage());
        }
    }

    public function findNearestPrice(float $price): ?Product
    {
        try {
            $sql = "SELECT * FROM products ORDER BY ABS(price - :price) ASC, id DESC LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['price' => $price]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->hydrateEntity($row, new Product()) : null;
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to find nearest price: ' . $e->getMessage());
        }
    }
} 