<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

use InvalidArgumentException;
use RuntimeException;

use function is_array;
use function is_callable;
use function max;

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

    private object $redis;

    /** Client must provide the PhpRedis-compatible eval(string, array, int) method. */
    public function __construct(object $redis)
    {
        $this->redis = $redis;

        if (!is_callable([$this->redis, 'eval'])) {
            throw new InvalidArgumentException('Redis rate-limit store requires an eval-capable client.');
        }
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
}
