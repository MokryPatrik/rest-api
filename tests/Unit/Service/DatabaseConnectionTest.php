<?php

namespace Tests\Unit\Service;

use App\Service\DatabaseConnection;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOException;
use ReflectionClass;

class DatabaseConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset the static connection
        $reflection = new ReflectionClass(DatabaseConnection::class);
        $property = $reflection->getProperty('connection');
        $property->setAccessible(true);
        $property->setValue(null, null);

        // Ensure environment variables are unset
        unset($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    }

    protected function tearDown(): void
    {
        // Reset environment variables
        unset($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    }

    public function testGetConnectionWithMissingCredentials(): void
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Database connection parameters are not set.');

        DatabaseConnection::getConnection();
    }

    public function testGetConnectionWithInvalidCredentials(): void
    {
        // Set up environment variables with invalid values
        $_ENV['DB_HOST'] = 'invalid_host';
        $_ENV['DB_NAME'] = 'invalid_db';
        $_ENV['DB_USER'] = 'invalid_user';
        $_ENV['DB_PASSWORD'] = 'invalid_password';

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Connection failed:');

        DatabaseConnection::getConnection();
    }

    public function testGetConnectionReturnsSameInstance(): void
    {
        // Set up environment variables
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'test_user';
        $_ENV['DB_PASSWORD'] = 'test_password';

        // Mock the PDO class
        $pdoMock = $this->createMock(PDO::class);
        
        // Use reflection to set the static connection
        $reflection = new ReflectionClass(DatabaseConnection::class);
        $property = $reflection->getProperty('connection');
        $property->setAccessible(true);
        $property->setValue(null, $pdoMock);
        
        // Get connection twice
        $connection1 = DatabaseConnection::getConnection();
        $connection2 = DatabaseConnection::getConnection();

        // Assert they are the same instance
        $this->assertSame($connection1, $connection2);
    }
} 