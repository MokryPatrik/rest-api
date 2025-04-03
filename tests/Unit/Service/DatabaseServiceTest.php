<?php

namespace Tests\Unit\Service;

use App\Service\DatabaseService;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOException;
use PHPUnit\Framework\MockObject\MockObject;

class DatabaseServiceTest extends TestCase
{
    private MockObject&PDO $pdoMock;
    private DatabaseService $databaseService;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->databaseService = new DatabaseService($this->pdoMock);
    }

    public function testGetConnection(): void
    {
        $connection = $this->databaseService->getConnection();
        $this->assertSame($this->pdoMock, $connection);
    }

    public function testBeginTransaction(): void
    {
        $this->pdoMock->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $this->databaseService->beginTransaction();
    }

    public function testCommit(): void
    {
        $this->pdoMock->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        $this->databaseService->commit();
    }

    public function testRollBack(): void
    {
        $this->pdoMock->expects($this->once())
            ->method('rollBack')
            ->willReturn(true);

        $this->databaseService->rollBack();
    }

    public function testTransactionFailure(): void
    {
        $this->pdoMock->expects($this->once())
            ->method('beginTransaction')
            ->willThrowException(new PDOException('Transaction failed'));

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Transaction failed');

        $this->databaseService->beginTransaction();
    }
} 