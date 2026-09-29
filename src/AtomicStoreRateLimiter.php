<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

use InvalidArgumentException;
use Psr\Clock\ClockInterface;

use function max;
use function time;

final readonly class AtomicStoreRateLimiter implements RateLimiterInterface
{
    public function __construct(
        private AtomicRateLimitStoreInterface $store,
        private ?ClockInterface $clock = null,
    ) {
    }

    public function hit(string $key, int $maxAttempts, int $decaySeconds): RateLimitResult
    {
        if ($maxAttempts < 1 || $decaySeconds < 1) {
            throw new InvalidArgumentException('Rate limit and decay must be positive integers.');
        }

        $counter   = $this->store->increment($key, $decaySeconds);
        $remaining = max(0, $maxAttempts - $counter->attempts);
        $now       = $this->clock?->now()->getTimestamp() ?? time();

        return new RateLimitResult(
            allowed: $counter->attempts <= $maxAttempts,
            limit: $maxAttempts,
            remaining: $remaining,
            retryAfterSeconds: $counter->retryAfterSeconds,
            resetAt: $now + $counter->retryAfterSeconds,
        );
    }

    public function attempts(string $key): int
    {
        return $this->store->attempts($key);
    }

    public function reset(string $key): void
    {
        $this->store->reset($key);
    }
}
