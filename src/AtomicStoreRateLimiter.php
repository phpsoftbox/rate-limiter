<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

use Closure;
use InvalidArgumentException;

use function max;
use function time;

final readonly class AtomicStoreRateLimiter implements RateLimiterInterface
{
    private Closure $clock;

    /** @param callable(): int|null $clock */
    public function __construct(
        private AtomicRateLimitStoreInterface $store,
        ?callable $clock = null,
    ) {
        $this->clock = $clock === null ? time(...) : Closure::fromCallable($clock);
    }

    public function hit(string $key, int $maxAttempts, int $decaySeconds): RateLimitResult
    {
        if ($maxAttempts < 1 || $decaySeconds < 1) {
            throw new InvalidArgumentException('Rate limit and decay must be positive integers.');
        }

        $counter   = $this->store->increment($key, $decaySeconds);
        $remaining = max(0, $maxAttempts - $counter->attempts);
        $now       = ($this->clock)();

        return new RateLimitResult(
            allowed: $counter->attempts <= $maxAttempts,
            limit: $maxAttempts,
            remaining: $remaining,
            retryAfterSeconds: $counter->retryAfterSeconds,
            resetAt: $now + $counter->retryAfterSeconds,
        );
    }
}
