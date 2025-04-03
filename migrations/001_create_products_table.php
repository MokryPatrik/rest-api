<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\DatabaseService;

try {
    $databaseService = new DatabaseService();
    $pdo = $databaseService->getConnection();

    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        brand VARCHAR(100) NOT NULL,
        category VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        INDEX idx_brand (brand),
        INDEX idx_category (category),
        INDEX idx_price (price),
        INDEX idx_brand_category (brand, category),
        UNIQUE INDEX idx_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql);
    echo "Products table created successfully!\n";
} catch (PDOException $e) {
    echo "Error creating products table: " . $e->getMessage() . "\n";
    exit(1);
} 