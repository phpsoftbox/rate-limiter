<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

use Redis;
use RedisCluster;
use RuntimeException;

use function is_array;
use function is_numeric;
use function max;

/**
 * Атомарное хранилище счётчиков на PhpRedis (ext-redis): `Redis` или `RedisCluster`.
 *
 * Другие клиенты (например, Predis с иной сигнатурой `eval()`) подключаются собственной
 * реализацией AtomicRateLimitStoreInterface.
 */
final readonly class RedisAtomicRateLimitStore implements AtomicRateLimitStoreInterface
{
    private const string LUA = <<<'LUA'
local current = redis.call('INCR', KEYS[1])
if current == 1 then
    redis.call('EXPIRE', KEYS[1], ARGV[1])
end
local ttl = redis.call('TTL', KEYS[1])
if ttl < 0 then
    redis.call('EXPIRE', KEYS[1], ARGV[1])
    ttl = tonumber(ARGV[1])
end
return {current, ttl}
LUA;

    public function __construct(
        private Redis|RedisCluster $redis,
    ) {
    }

    public function increment(string $key, int $decaySeconds): RateLimitCounter
    {
        $result = $this->redis->eval(self::LUA, [$key, $decaySeconds], 1);
        if (!is_array($result) || !isset($result[0], $result[1])) {
            throw new RuntimeException('Redis rate-limit store returned an invalid Lua result.');
        }

        return new RateLimitCounter(
            attempts: (int) $result[0],
            retryAfterSeconds: max(1, (int) $result[1]),
        );
    }

    public function attempts(string $key): int
    {
        $value = $this->redis->get($key);

        return is_numeric($value) ? (int) $value : 0;
    }

    public function reset(string $key): void
    {
        $this->redis->del($key);
    }
}
