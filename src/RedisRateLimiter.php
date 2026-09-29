<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

use Psr\Clock\ClockInterface;
use Redis;
use RedisCluster;

final readonly class RedisRateLimiter implements RateLimiterInterface
{
    private AtomicStoreRateLimiter $limiter;

    public function __construct(
        Redis|RedisCluster $redis,
        ?ClockInterface $clock = null,
    ) {
        $this->limiter = new AtomicStoreRateLimiter(new RedisAtomicRateLimitStore($redis), $clock);
    }

    public function hit(string $key, int $maxAttempts, int $decaySeconds): RateLimitResult
    {
        return $this->limiter->hit($key, $maxAttempts, $decaySeconds);
    }

    public function attempts(string $key): int
    {
        return $this->limiter->attempts($key);
    }

    public function reset(string $key): void
    {
        $this->limiter->reset($key);
    }
}
