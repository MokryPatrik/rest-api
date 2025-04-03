<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepositoryInterface;
use App\Exception\ProductNotFoundException;
use App\Exception\DatabaseException;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

abstract readonly class AbstractRepository
{
    protected function jsonErrorResponse(string $message, int $statusCode): JsonResponse
    {
        return new JsonResponse(
            [
                'success' => false,
                'message' => $message,
                'data' => [],
            ],
            $statusCode,
        );
    }

    protected function jsonSuccessResponse(
        array $data,
        ?string $message = null,
        int $statusCode = Response::HTTP_OK,
    ): JsonResponse {
        return new JsonResponse(
            [
                'success' => true,
                'message' => $message,
                'data' => $data,
            ],
            $statusCode,
        );
    }

    protected function decodeJson(Request $request): array
    {
        $content = $request->getContent();
        if (empty($content)) {
            throw new InvalidArgumentException('Request body is empty.');
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
} 