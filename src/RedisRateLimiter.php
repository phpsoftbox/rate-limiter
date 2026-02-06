<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

final readonly class RedisRateLimiter implements RateLimiterInterface
{
    private AtomicStoreRateLimiter $limiter;

    /**
     * Client must provide the PhpRedis-compatible eval(string, array, int) method.
     *
     * @param callable(): int|null $clock
     */
    public function __construct(
        object $redis,
        ?callable $clock = null,
    ) {
        $this->limiter = new AtomicStoreRateLimiter(new RedisAtomicRateLimitStore($redis), $clock);
    }

    public function hit(string $key, int $maxAttempts, int $decaySeconds): RateLimitResult
    {
        return $this->limiter->hit($key, $maxAttempts, $decaySeconds);
    }
}
