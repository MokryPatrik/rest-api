<?php

namespace App\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class BearerTokenMiddleware
{
    private string $token;

    public function __construct()
    {
        $this->token = $_ENV['APP_BEARER_TOKEN'];
    }

    public function handle(Request $request): ?Response
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !preg_match('/^Bearer\s+(.*)$/', $authHeader, $matches)) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Missing or invalid Authorization header.',
                    'data' => []
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $token = $matches[1];
        
        if ($token !== $this->token) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Invalid token.',
                    'data' => []
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return null;
    }
}
