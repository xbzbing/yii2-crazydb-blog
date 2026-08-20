<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Predis\ClientInterface;
use Predis\Command\CommandInterface;

/** 所有命令抛异常，模拟 Redis 不可用（用于降级路径测试） */
final class FailingRedisStub implements ClientInterface
{
    /**
     * @param string $method
     * @param list<mixed> $arguments
     */
    public function __call($method, $arguments): mixed
    {
        throw new \RuntimeException('redis down');
    }

    public function getProfile(): never
    {
        throw new \LogicException('not used in tests');
    }

    public function getCommandFactory(): never
    {
        throw new \LogicException('not used in tests');
    }

    public function getOptions(): never
    {
        throw new \LogicException('not used in tests');
    }

    public function connect(): void
    {
    }

    public function disconnect(): void
    {
    }

    public function getConnection(): never
    {
        throw new \LogicException('not used in tests');
    }

    /**
     * @param string $method
     * @param list<mixed> $arguments
     */
    public function createCommand($method, $arguments = []): never
    {
        throw new \LogicException('not used in tests');
    }

    public function executeCommand(CommandInterface $command): never
    {
        throw new \LogicException('not used in tests');
    }
}