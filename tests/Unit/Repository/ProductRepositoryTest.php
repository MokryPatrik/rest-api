<?php

namespace Tests\Unit\Repository;

use App\Entity\Product;
use App\Exception\DatabaseException;
use App\Exception\ProductNotFoundException;
use App\Exception\UniqueConstraintException;
use App\Repository\ProductRepository;
use App\Repository\ProductRepositoryInterface;
use App\Service\DatabaseService;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;

class ProductRepositoryTest extends TestCase
{
    private MockObject&PDO $pdoMock;
    private MockObject&DatabaseService $databaseServiceMock;
    private ProductRepository $productRepository;

    protected function setUp(): void
    {
        // Create mock for PDO
        $this->pdoMock = $this->createMock(PDO::class);
        
        // Create mock for DatabaseService
        $this->databaseServiceMock = $this->createMock(DatabaseService::class);
        $this->databaseServiceMock->method('getConnection')->willReturn($this->pdoMock);
        
        // Create ProductRepository with mocked dependencies
        $this->productRepository = new ProductRepository($this->databaseServiceMock);
    }

    public function testCreateProductSuccess(): void
    {
        // Arrange
        $product = new Product();
        $product->setName('Test Product');
        $product->setDescription('Test Description');
        $product->setBrand('Test Brand');
        $product->setCategory('Test Category');
        $product->setPrice(99.99);
        
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                'name' => 'Test Product',
                'description' => 'Test Description',
                'brand' => 'Test Brand',
                'category' => 'Test Category',
                'price' => 99.99
            ])
            ->willReturn(true);
        
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO products'))
            ->willReturn($stmtMock);
        
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('1');
        
        // Act
        $result = $this->productRepository->create($product);
        
        // Assert
        $this->assertEquals(1, $result);
    }

    public function testCreateProductDatabaseError(): void
    {
        // Arrange
        $product = new Product();
        $product->setName('Test Product');
        $product->setDescription('Test Description');
        $product->setBrand('Test Brand');
        $product->setCategory('Test Category');
        $product->setPrice(99.99);
        
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new PDOException('Connection error'));
        
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);
        
        // Assert
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Failed to create product: Connection error');
        
        // Act
        $this->productRepository->create($product);
    }

    public function testUpdateProductSuccess(): void
    {
        // Arrange
        $product = new Product();
        $product->setId(1);
        $product->setName('Updated Product');
        $product->setDescription('Updated Description');
        $product->setBrand('Updated Brand');
        $product->setCategory('Updated Category');
        $product->setPrice(149.99);
        
        // Mock findById statement
        $findStmtMock = $this->createMock(PDOStatement::class);
        $findStmtMock->expects($this->once())
            ->method('execute')
            ->with(['id' => 1])
            ->willReturn(true);
        $findStmtMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'name' => 'Original Product',
                'description' => 'Original Description',
                'brand' => 'Original Brand',
                'category' => 'Original Category',
                'price' => 99.99
            ]);
        
        // Mock update statement
        $updateStmtMock = $this->createMock(PDOStatement::class);
        $updateStmtMock->expects($this->once())
            ->method('execute')
            ->with([
                'id' => 1,
                'name' => 'Updated Product',
                'description' => 'Updated Description',
                'brand' => 'Updated Brand',
                'category' => 'Updated Category',
                'price' => 149.99
            ])
            ->willReturn(true);
        
        // Set up PDO mock to return different statements for different queries
        $prepareCallCount = 0;
        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function($sql) use (&$prepareCallCount, $findStmtMock, $updateStmtMock) {
                $prepareCallCount++;
                if (str_contains($sql, 'SELECT * FROM products WHERE id = :id')) {
                    return $findStmtMock;
                }
                if (str_contains($sql, 'UPDATE products')) {
                    return $updateStmtMock;
                }
                throw new \RuntimeException('Unexpected SQL query: ' . $sql);
            });
        
        // Act
        $result = $this->productRepository->update($product);
        
        // Assert
        $this->assertTrue($result);
    }

    public function testUpdateProductNotFound(): void
    {
        // Arrange
        $product = new Product();
        $product->setId(1);
        $product->setName('Updated Product');
        $product->setDescription('Updated Description');
        $product->setBrand('Updated Brand');
        $product->setCategory('Updated Category');
        $product->setPrice(149.99);
        
        // Mock findById statement to return null (product not found)
        $findStmtMock = $this->createMock(PDOStatement::class);
        $findStmtMock->expects($this->once())
            ->method('execute')
            ->with(['id' => 1])
            ->willReturn(true);
        $findStmtMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);
        
        // Set up PDO mock to return the find statement
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM products WHERE id = :id'))
            ->willReturn($findStmtMock);
        
        // Assert
        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage('Product with ID 1 not found.');
        
        // Act
        $this->productRepository->update($product);
    }

    public function testUpdateProductWithoutId(): void
    {
        // Arrange
        $product = new Product();
        $product->setName('Updated Product');
        $product->setDescription('Updated Description');
        $product->setBrand('Updated Brand');
        $product->setCategory('Updated Category');
        $product->setPrice(149.99);
        
        // Assert
        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage('Cannot update product without ID');
        
        // Act
        $this->productRepository->update($product);
    }

    public function testDeleteProductSuccess(): void
    {
        // Arrange
        $id = 1;
        
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with(['id' => $id])
            ->willReturn(true);
        
        $stmtMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);
        
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM products'))
            ->willReturn($stmtMock);
        
        // Act
        $result = $this->productRepository->delete($id);
        
        // Assert
        $this->assertTrue($result);
    }

    public function testDeleteProductNotFound(): void
    {
        // Arrange
        $id = 1;
        
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        
        $stmtMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);
        
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);
        
        // Assert
        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage('Product with ID 1 not found');
        
        // Act
        $this->productRepository->delete($id);
    }

    public function testFindAllWithNoFilters(): void
    {
        // Arrange
        $expectedProducts = [
            [
                'id' => 1,
                'name' => 'Product 1',
                'description' => 'Description 1',
                'brand' => 'Brand 1',
                'category' => 'Category 1',
                'price' => 99.99
            ],
            [
                'id' => 2,
                'name' => 'Product 2',
                'description' => 'Description 2',
                'brand' => 'Brand 2',
                'category' => 'Category 2',
                'price' => 149.99
            ]
        ];
        
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        
        $stmtMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedProducts);
        
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM products'))
            ->willReturn($stmtMock);
        
        // Act
        $result = $this->productRepository->findAll();
        
        // Assert
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Product::class, $result[0]);
        $this->assertEquals(1, $result[0]->getId());
        $this->assertEquals('Product 1', $result[0]->getName());
        $this->assertEquals(2, $result[1]->getId());
        $this->assertEquals('Product 2', $result[1]->getName());
    }

    public function testFindAllWithFilters(): void
    {
        // Arrange
        $filters = [
            'brand' => 'Test Brand',
            'category' => 'Test Category'
        ];
        
        $expectedProducts = [
            [
                'id' => 1,
                'name' => 'Product 1',
                'description' => 'Description 1',
                'brand' => 'Test Brand',
                'category' => 'Test Category',
                'price' => 99.99
            ]
        ];
        
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        
        $stmtMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedProducts);
        
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE brand = :brand AND category = :category'))
            ->willReturn($stmtMock);
        
        // Act
        $result = $this->productRepository->findAll($filters);
        
        // Assert
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Product::class, $result[0]);
        $this->assertEquals(1, $result[0]->getId());
        $this->assertEquals('Product 1', $result[0]->getName());
        $this->assertEquals('Test Brand', $result[0]->getBrand());
        $this->assertEquals('Test Category', $result[0]->getCategory());
    }

    public function testFindByIdSuccess(): void
    {
        // Arrange
        $id = 1;
        $expectedProduct = [
            'id' => 1,
            'name' => 'Product 1',
            'description' => 'Description 1',
            'brand' => 'Brand 1',
            'category' => 'Category 1',
            'price' => 99.99
        ];
        
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with(['id' => $id])
            ->willReturn(true);
        
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedProduct);
        
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM products WHERE id = :id'))
            ->willReturn($stmtMock);
        
        // Act
        $result = $this->productRepository->findById($id);
        
        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals('Product 1', $result->getName());
        $this->assertEquals('Description 1', $result->getDescription());
        $this->assertEquals('Brand 1', $result->getBrand());
        $this->assertEquals('Category 1', $result->getCategory());
        $this->assertEquals(99.99, $result->getPrice());
    }

    public function testFindByIdNotFound(): void
    {
        // Arrange
        $id = 1;
        
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with(['id' => $id])
            ->willReturn(true);
        
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);
        
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM products WHERE id = :id'))
            ->willReturn($stmtMock);
        
        // Act
        $result = $this->productRepository->findById($id);
        
        // Assert
        $this->assertNull($result);
    }

    public function testFindNearestPriceSuccess(): void
    {
        // Arrange
        $price = 100.00;
        $expectedProduct = [
            'id' => 1,
            'name' => 'Product 1',
            'description' => 'Description 1',
            'brand' => 'Brand 1',
            'category' => 'Category 1',
            'price' => 99.99
        ];
        
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with(['price' => $price])
            ->willReturn(true);
        
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedProduct);
        
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('ORDER BY ABS(price - :price) ASC'))
            ->willReturn($stmtMock);
        
        // Act
        $result = $this->productRepository->findNearestPrice($price);
        
        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals('Product 1', $result->getName());
        $this->assertEquals(99.99, $result->getPrice());
    }

    public function testFindNearestPriceNotFound(): void
    {
        // Arrange
        $price = 100.00;
        
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with(['price' => $price])
            ->willReturn(true);
        
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);
        
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('ORDER BY ABS(price - :price) ASC'))
            ->willReturn($stmtMock);
        
        // Act
        $result = $this->productRepository->findNearestPrice($price);
        
        // Assert
        $this->assertNull($result);
    }
} 