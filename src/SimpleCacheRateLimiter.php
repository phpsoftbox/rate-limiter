<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

use InvalidArgumentException;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;

use function is_array;
use function is_int;
use function time;

/**
 * Fixed-window лимитер поверх PSR-16 get/set — неатомарный, только для development, тестов
 * и single-process окружения.
 */
final readonly class SimpleCacheRateLimiter implements RateLimiterInterface
{
    public function __construct(
        private CacheInterface $cache,
        private ?ClockInterface $clock = null,
    ) {
    }

    public function hit(string $key, int $maxAttempts, int $decaySeconds): RateLimitResult
    {
        if ($maxAttempts < 1 || $decaySeconds < 1) {
            throw new InvalidArgumentException('Rate limit and decay must be positive integers.');
        }

        $now  = $this->now();
        $data = $this->window($key, $now) ?? ['count' => 0, 'reset' => $now + $decaySeconds];

        $data['count']++;

        $retryAfter = $data['reset'] - $now;
        $this->cache->set($key, $data, $retryAfter);

        $remaining = $maxAttempts - $data['count'];

        return new RateLimitResult(
            allowed: $remaining >= 0,
            limit: $maxAttempts,
            remaining: $remaining >= 0 ? $remaining : 0,
            retryAfterSeconds: $retryAfter,
            resetAt: $data['reset'],
        );
    }

    public function attempts(string $key): int
    {
        return $this->window($key, $this->now())['count'] ?? 0;
    }

    public function reset(string $key): void
    {
        $this->cache->delete($key);
    }

    /**
     * @return array{count: int, reset: int}|null Активное окно ключа или null, если его нет или оно истекло.
     */
    private function window(string $key, int $now): ?array
    {
        $data = $this->cache->get($key);

        if (!is_array($data) || !is_int($data['count'] ?? null) || !is_int($data['reset'] ?? null)) {
            return null;
        }

        if ($data['reset'] <= $now) {
            return null;
        }

        return ['count' => $data['count'], 'reset' => $data['reset']];
    }

    private function now(): int
    {
        return $this->clock?->now()->getTimestamp() ?? time();
    }
}
