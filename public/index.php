<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/routes.php';

global $routes;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;
use App\Middleware\BearerTokenMiddleware;

// Initialize container
$container = new ContainerBuilder();

// Register services manually or with autowiring
$container->register('database_service', App\Service\DatabaseService::class)
    ->setAutowired(true)
    ->setAutoconfigured(true)
    ->setPublic(true)
;

// Register BearerTokenMiddleware with the same pattern as database_service
$container->register('bearer_token_middleware', BearerTokenMiddleware::class)
    ->setAutowired(true)
    ->setAutoconfigured(true)
    ->setPublic(true)
;

// Register ProductRepository with DatabaseService dependency
$container->register('product_repository', App\Repository\ProductRepository::class)
    ->setAutowired(true)
    ->setAutoconfigured(true)
    ->setPublic(true)
    ->setArguments([
        new Reference('database_service')
    ])
;

// Register ProductRepositoryInterface as an alias to ProductRepository
$container->setAlias(App\Repository\ProductRepositoryInterface::class, 'product_repository')
    ->setPublic(true);

// Register the controller with explicit args
$container->register('product_controller', App\Controller\ProductController::class)
    ->setAutowired(true)
    ->setAutoconfigured(true)
    ->setPublic(true)
    ->setArguments([
        new Reference('product_repository')
    ])
;


// Compile the container
$container->compile();

// Create request from globals
$request = Request::createFromGlobals();

// Initialize routing
$context = new RequestContext();
$context->fromRequest($request);
$matcher = new UrlMatcher($routes, $context);

try {
    // Check bearer token first
    $middleware = $container->get('bearer_token_middleware');
    $middlewareResponse = $middleware->handle($request);

    if ($middlewareResponse !== null) {
        $middlewareResponse->send();
        exit;
    }

    // Match the request
    $parameters = $matcher->match($request->getPathInfo());
    
    // Get the controller service and method
    list($controllerService, $method) = $parameters['_controller'];
    
    // Get the controller instance
    $controller = $container->get($controllerService);
    
    // Remove the controller from parameters
    unset($parameters['_controller']);
    
    // Call the controller method
    $response = $controller->$method($request, $parameters);
} catch (ResourceNotFoundException $e) {
    $response = new Response('Not Found', 404);
} catch (JsonException $e) {
    $response = new Response('Unprocessable Entity', 422);
} catch (Exception $e) {
    $response = new Response('Internal Server Error', 500);
}
// Send the response
$response->send();
