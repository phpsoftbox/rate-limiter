<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

use function is_array;
use function time;

final class SimpleCacheRateLimiter implements RateLimiterInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public function hit(string $key, int $maxAttempts, int $decaySeconds): RateLimitResult
    {
        if ($maxAttempts < 1 || $decaySeconds < 1) {
            throw new InvalidArgumentException('Rate limit and decay must be positive integers.');
        }

        $now  = time();
        $data = $this->cache->get($key);

        if (!is_array($data) || !isset($data['count'], $data['reset'])) {
            $data = ['count' => 0, 'reset' => $now + $decaySeconds];
        }

        if ($data['reset'] <= $now) {
            $data = ['count' => 0, 'reset' => $now + $decaySeconds];
        }

        $data['count']++;

        $retryAfter = $data['reset'] - $now;
        $this->cache->set($key, $data, $retryAfter > 0 ? $retryAfter : 1);

        $remaining = $maxAttempts - $data['count'];
        $allowed   = $remaining >= 0;

        return new RateLimitResult(
            allowed: $allowed,
            limit: $maxAttempts,
            remaining: $remaining >= 0 ? $remaining : 0,
            retryAfterSeconds: $retryAfter,
            resetAt: $data['reset'],
        );
    }
}
