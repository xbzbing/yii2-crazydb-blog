<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Predis\ClientInterface;

/**
 * 内存 Redis 桩：真实语义的 key-value + HLL（数组去重）+ SCAN，
 * 并记录全部调用。用于同步命令（visit/sync、post-view/sync）的回归测试。
 */
final class InMemoryRedisStub implements ClientInterface
{
    /** @var array<string, string|list<string>|array<string, int>> key → 字符串/集合成员/HLL/ZSET */
    public array $store = [];

    /** @var list<array{0: string, 1: list<mixed>}> */
    public array $calls = [];

    /**
     * @param string $method
     * @param list<mixed> $arguments
     */
    public function __call($method, $arguments): mixed
    {
        $this->calls[] = [(string)$method, array_values((array)$arguments)];
        return match ($method) {
            'get' => $this->store[$arguments[0]] ?? null,
            'set' => $this->set($arguments),
            'del' => $this->delete($arguments[0]),
            'expire' => true,
            'pfadd' => $this->pfadd((string)$arguments[0], (array)$arguments[1]),
            'pfcount' => $this->pfcount((string)$arguments[0]),
            'sadd' => $this->sadd((string)$arguments[0], (array)$arguments[1]),
            'smembers' => $this->smembers((string)$arguments[0]),
            'srem' => $this->srem((string)$arguments[0], (array)$arguments[1]),
            'scard' => $this->scard((string)$arguments[0]),
            'zincrby' => $this->zincrby((string)$arguments[0], (string)$arguments[1], (string)$arguments[2]),
            'zrevrange' => $this->zrevrange($arguments),
            'scan' => $this->scan((int)$arguments[0], (array)($arguments[1] ?? [])),
            'pipeline' => $this->runPipeline($arguments),
            default => 1,
        };
    }

    /**
     * set：支持 (key, value) 与 RedisLock 的 (key, value, EX, ttl, NX) 形式。
     *
     * @param list<mixed> $arguments
     */
    private function set(array $arguments): mixed
    {
        $key = (string)$arguments[0];
        // SET key value [EX ttl] [NX]（RedisLock::acquire 使用）
        $hasNx = in_array('NX', $arguments, true);
        if ($hasNx) {
            if (isset($this->store[$key])) {
                return null; // 已存在 → 获取锁失败
            }
        }
        $value = (string)($arguments[1] ?? '');
        $this->store[$key] = $value;
        return $hasNx ? 'OK' : $this->store[$key];
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

    public function executeCommand(\Predis\Command\CommandInterface $command): never
    {
        throw new \LogicException('not used in tests');
    }

    private function delete(mixed $keys): int
    {
        $keys = (array)$keys;
        $deleted = 0;
        foreach ($keys as $key) {
            if (isset($this->store[(string)$key])) {
                unset($this->store[(string)$key]);
                $deleted++;
            }
        }
        return $deleted;
    }

    /**
     * @param string $key
     * @param list<mixed> $members
     */
    private function pfadd(string $key, array $members): int
    {
        /** @var list<string> $hll */
        $hll = $this->store[$key] ?? [];
        $added = 0;
        foreach ($members as $member) {
            if (!in_array((string)$member, $hll, true)) {
                $hll[] = (string)$member;
                $added++;
            }
        }
        $this->store[$key] = $hll;
        return $added;
    }

    private function pfcount(string $key): int
    {
        /** @var list<string> $hll */
        $hll = $this->store[$key] ?? [];
        return count($hll);
    }

    /**
     * @param string $key
     * @param list<mixed> $members
     */
    private function sadd(string $key, array $members): int
    {
        /** @var list<string> $set */
        $set = $this->store[$key] ?? [];
        $added = 0;
        foreach ($members as $member) {
            if (!in_array((string)$member, $set, true)) {
                $set[] = (string)$member;
                $added++;
            }
        }
        $this->store[$key] = $set;
        return $added;
    }

    /**
     * @return list<string>
     */
    private function smembers(string $key): array
    {
        /** @var list<string> $set */
        $set = $this->store[$key] ?? [];
        sort($set);
        return $set;
    }

    /**
     * @param string $key
     * @param list<mixed> $members
     */
    private function srem(string $key, array $members): int
    {
        /** @var list<string> $set */
        $set = $this->store[$key] ?? [];
        $before = count($set);
        foreach ($members as $member) {
            $set = array_values(array_filter($set, static fn (string $v): bool => $v !== (string)$member));
        }
        $this->store[$key] = $set;
        return $before - count($set);
    }

    private function scard(string $key): int
    {
        /** @var list<string> $set */
        $set = $this->store[$key] ?? [];
        return count($set);
    }

    private function zincrby(string $key, string $increment, string $member): string
    {
        /** @var array<string, int> $zset */
        $zset = $this->store[$key] ?? [];
        $zset[$member] = ($zset[$member] ?? 0) + (int)$increment;
        $this->store[$key] = $zset;
        return (string)$zset[$member];
    }

    /**
     * @param list<mixed> $arguments
     * @return array<string, string>
     */
    private function zrevrange(array $arguments): array
    {
        $key = (string)$arguments[0];
        $start = (int)$arguments[1];
        $stop = (int)$arguments[2];
        /** @var array<string, int> $zset */
        $zset = $this->store[$key] ?? [];
        // 降序按分值，关联数组 {member: score} 与 Predis zrevrange withscores 行为一致
        arsort($zset);
        $pairs = [];
        foreach ($zset as $member => $score) {
            $pairs[$member] = (string)$score;
        }
        return array_slice($pairs, max(0, $start), $stop >= 0 ? $stop - $start + 1 : null, true);
    }

    /**
     * @param int $cursor
     * @param array<string, mixed> $options
     * @return array{0: string, 1: list<string>}
     */
    private function scan(int $cursor, array $options): array
    {
        $match = (string)($options['match'] ?? '*');
        $prefix = rtrim($match, '*');
        $found = [];
        foreach (array_keys($this->store) as $key) {
            if (str_starts_with($key, $prefix)) {
                $found[] = $key;
            }
        }
        sort($found);
        return ['0', $found];
    }

    /**
     * @param list<mixed> $arguments
     * @return list<mixed>
     */
    private function runPipeline(array $arguments): array
    {
        if (isset($arguments[0]) && $arguments[0] instanceof \Closure) {
            $pipe = new PipelineRecorder($this->store, $this->calls);
            ($arguments[0])($pipe);
        }
        return [];
    }
}

/**
 * pipeline 内部命令桩：命令写入共享 store/calls。
 */
final class PipelineRecorder
{
    /**
     * 外部 stub 的共享 calls 数组（经引用传回），本类只写不读，由外部测试断言读取。
     *
     * @var list<array{0: string, 1: list<mixed>}>
     * @phpstan-ignore property.onlyWritten
     */
    private array $calls = [];

    /**
     * @param array<string, string|list<string>> $store
     * @param list<array{0: string, 1: list<mixed>}> $calls
     */
    public function __construct(
        private array &$store,
        array &$calls,
    ) {
        $this->calls = &$calls;
    }

    /**
     * @param string $method
     * @param list<mixed> $arguments
     */
    public function __call($method, $arguments): static
    {
        $arguments = array_values((array)$arguments);
        $this->calls[] = [(string)$method, $arguments];
        switch ($method) {
            case 'incr':
                $this->store[$arguments[0]] = (string)(((int)($this->store[$arguments[0]] ?? 0)) + 1);
                break;
            case 'pfadd':
                /** @var list<string> $hll */
                $hll = $this->store[$arguments[0]] ?? [];
                foreach ($arguments[1] as $member) {
                    if (!in_array((string)$member, $hll, true)) {
                        $hll[] = (string)$member;
                    }
                }
                $this->store[$arguments[0]] = $hll;
                break;
            case 'expire':
                break;
            default:
                $this->store[$arguments[0]] = (string)($arguments[1] ?? '');
        }
        return $this;
    }
}