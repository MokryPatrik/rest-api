<?php

namespace App\Controller;

use App\Entity\Product;
use App\Exception\UniqueConstraintException;
use App\Repository\ProductRepositoryInterface;
use App\Exception\ProductNotFoundException;
use App\Service\HydrationTrait;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class ProductController extends AbstractRepository
{
    use HydrationTrait;

    public function __construct(private readonly ProductRepositoryInterface $productRepository)
    {}

    /**
     * @throws JsonException
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $data = $this->decodeJson($request);

            $product = $this->hydrateEntity($data, new Product());

            $id = $this->productRepository->create($product);
            $product->setId($id);

            return $this->jsonSuccessResponse($product->toArray(), null, Response::HTTP_CREATED);
        } catch (UniqueConstraintException $e) {
            return $this->jsonErrorResponse($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @throws JsonException
     */
    public function update(Request $request, array $parameters): JsonResponse
    {
        try {
            if (!isset($parameters['id']) || !is_numeric($parameters['id'])) {
                return $this->jsonErrorResponse('Product ID is required.', Response::HTTP_BAD_REQUEST);
            }

            $data = $this->decodeJson($request);

            $product = $this->hydrateEntity($data, new Product());
            $product->setId($parameters['id']);

            $this->productRepository->update($product);

            return $this->jsonSuccessResponse($product->toArray());
        } catch (ProductNotFoundException $e) {
            return $this->jsonErrorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException $e) {
            return $this->jsonErrorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (UniqueConstraintException $e) {
            return $this->jsonErrorResponse($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function delete(Request $request, array $parameters): JsonResponse
    {
        if (!isset($parameters['id']) || !is_numeric($parameters['id'])) {
            return $this->jsonErrorResponse('Product ID is required.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->productRepository->delete($parameters['id']);

            return $this->jsonSuccessResponse([], 'Product deleted successfully.');
        } catch (ProductNotFoundException $e) {
            return $this->jsonErrorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }

    public function getAll(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('per_page', 10)));

        $filters = array_filter([
            'brand' => $request->query->get('brand'),
            'category' => $request->query->get('category'),
            'min_price' => $request->query->get('min_price'),
            'max_price' => $request->query->get('max_price')
        ]);

        $products = $this->productRepository->findAll($filters, $page, $perPage);

        return new JsonResponse([
            'success' => true,
            'data' => array_map(static fn(Product $product) => $product->toArray(), $products)
        ]);
    }

    public function getById(Request $request, array $parameters): JsonResponse
    {
        if (!isset($parameters['id']) || !is_numeric($parameters['id'])) {
            return $this->jsonErrorResponse('Product ID is required.', Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->findById($parameters['id']);

        if (!$product) {
            return $this->jsonErrorResponse('Product not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->jsonSuccessResponse($product->toArray());
    }

    public function findNearestPrice(Request $request): JsonResponse
    {
        $price = (float) $request->query->get('price', 0);

        if ($price <= 0) {
            return $this->jsonErrorResponse('Price must be greater than 0', Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->findNearestPrice($price);

        if (!$product) {
            return $this->jsonErrorResponse('No product found with the nearest price.', Response::HTTP_NOT_FOUND);
        }

        return $this->jsonSuccessResponse($product->toArray());
    }
} 