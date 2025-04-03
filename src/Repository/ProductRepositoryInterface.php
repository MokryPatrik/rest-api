<?php

namespace App\Repository;

use App\Entity\Product;

interface ProductRepositoryInterface
{
    public function create(Product $product): int;
    public function update(Product $product): bool;
    public function delete(int $id): bool;
    public function findAll(array $filters = [], int $page = 1, int $perPage = 10): array;
    public function findById(int $id): ?Product;
    public function findNearestPrice(float $price): ?Product;
} 