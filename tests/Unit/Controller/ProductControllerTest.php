<?php

namespace Tests\Unit\Controller;

use App\Controller\ProductController;
use App\Entity\Product;
use App\Exception\ProductNotFoundException;
use App\Exception\UniqueConstraintException;
use App\Repository\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductControllerTest extends TestCase
{
    private MockObject|ProductRepositoryInterface $productRepository;
    private ProductController $controller;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->controller = new ProductController($this->productRepository);
    }

    private function getResponseData(JsonResponse $response): array
    {
        return json_decode($response->getContent(), true);
    }

    private function createProduct(array $data = []): Product
    {
        $defaultData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'brand' => 'Test Brand',
            'category' => 'Test Category',
            'description' => 'Test Description'
        ];

        $productData = array_merge($defaultData, $data);

        return (new Product())
            ->setName($productData['name'])
            ->setPrice($productData['price'])
            ->setBrand($productData['brand'])
            ->setCategory($productData['category'])
            ->setDescription($productData['description'])
        ;
    }

    public function testCreateProductSuccess(): void
    {
        $productData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'brand' => 'Test Brand',
            'category' => 'Test Category'
        ];

        $request = new Request([], [], [], [], [], [], json_encode($productData));
        
        $this->productRepository
            ->expects($this->once())
            ->method('create')
            ->willReturn(1);

        $response = $this->controller->create($request);
        $responseData = $this->getResponseData($response);
        
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals($productData['name'], $responseData['data']['name']);
    }

    public function testCreateProductUniqueConstraintError(): void
    {
        $productData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'brand' => 'Test Brand',
            'category' => 'Test Category'
        ];

        $request = new Request([], [], [], [], [], [], json_encode($productData));
        
        $this->productRepository
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new UniqueConstraintException('Product already exists'));

        $response = $this->controller->create($request);
        $responseData = $this->getResponseData($response);
        
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
    }

    public function testUpdateProductSuccess(): void
    {
        $productData = [
            'name' => 'Updated Product',
            'price' => 149.99,
            'brand' => 'Updated Brand',
            'category' => 'Updated Category'
        ];

        $request = new Request([], [], [], [], [], [], json_encode($productData));
        $parameters = ['id' => 1];

        $this->productRepository
            ->expects($this->once())
            ->method('update')
            ->willReturn(true);

        $response = $this->controller->update($request, $parameters);
        $responseData = $this->getResponseData($response);
        
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals($productData['name'], $responseData['data']['name']);
    }

    public function testUpdateProductNotFound(): void
    {
        $productData = [
            'name' => 'Updated Product',
            'price' => 149.99,
            'brand' => 'Updated Brand',
            'category' => 'Updated Category'
        ];

        $request = new Request([], [], [], [], [], [], json_encode($productData));
        $parameters = ['id' => 999];

        $this->productRepository
            ->expects($this->once())
            ->method('update')
            ->willThrowException(new ProductNotFoundException('Product not found'));

        $response = $this->controller->update($request, $parameters);
        $responseData = $this->getResponseData($response);
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
    }

    public function testDeleteProductSuccess(): void
    {
        $parameters = ['id' => 1];
        $request = new Request();

        $this->productRepository
            ->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);

        $response = $this->controller->delete($request, $parameters);
        $responseData = $this->getResponseData($response);
        
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
    }

    public function testGetAllProductsSuccess(): void
    {
        $request = new Request();
        $products = [
            $this->createProduct(['name' => 'Product 1']),
            $this->createProduct(['name' => 'Product 2'])
        ];

        $this->productRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($products);

        $response = $this->controller->getAll($request);
        $responseData = $this->getResponseData($response);
        
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertCount(2, $responseData['data']);
    }

    public function testGetProductByIdSuccess(): void
    {
        $parameters = ['id' => 1];
        $request = new Request();
        $product = $this->createProduct(['name' => 'Test Product']);

        $this->productRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($product);

        $response = $this->controller->getById($request, $parameters);
        $responseData = $this->getResponseData($response);
        
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
    }

    public function testFindNearestPriceSuccess(): void
    {
        $request = new Request(['price' => 100]);
        $product = $this->createProduct(['name' => 'Nearest Price Product']);

        $this->productRepository
            ->expects($this->once())
            ->method('findNearestPrice')
            ->with(100.0)
            ->willReturn($product);

        $response = $this->controller->findNearestPrice($request);
        $responseData = $this->getResponseData($response);
        
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
    }

    public function testFindNearestPriceInvalidPrice(): void
    {
        $request = new Request(['price' => 0]);

        $response = $this->controller->findNearestPrice($request);
        $responseData = $this->getResponseData($response);
        
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
    }
} 