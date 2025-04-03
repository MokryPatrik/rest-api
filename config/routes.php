<?php

// Define routes
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();


addRouteHelper(
    'products_list',
    '/api/products',
    ['product_controller', 'getAll'],
    'GET'
);

addRouteHelper(
    'products_create',
    '/api/products',
    ['product_controller', 'create'],
    'POST'
);

addRouteHelper(
    'products_nearest_price',
    '/api/products/nearest-price',
    ['product_controller', 'findNearestPrice'],
    'GET'
);


addRouteHelper(
    'products_update',
    '/api/products/{id}',
    ['product_controller', 'update'],
    'PUT'
);

addRouteHelper(
    'products_delete',
    '/api/products/{id}',
    ['product_controller', 'delete'],
    'DELETE'
);

addRouteHelper(
    'products_get',
    '/api/products/{id}',
    ['product_controller', 'getById'],
    'GET'
);

function addRouteHelper(string $name, string $path, array $controller, string $method): void
{
    global $routes;

    $routes->add(
        $name,
        new Route(
            $path,
            [
                '_controller' => $controller
            ],
            [],
            [],
            '',
            [],
            [$method],
        )
    );
}