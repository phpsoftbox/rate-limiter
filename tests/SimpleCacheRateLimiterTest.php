<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter\Tests;

use PhpSoftBox\RateLimiter\SimpleCacheRateLimiter;
use PhpSoftBox\RateLimiter\Tests\Fixtures\ArrayCache;
use PHPUnit\Framework\TestCase;

use function time;

final class SimpleCacheRateLimiterTest extends TestCase
{
    /**
     * Проверяем, что лимитер считает попытки и возвращает оставшееся число.
     */
    public function testCountsAttempts(): void
    {
        $cache = new ArrayCache();

        $limiter = new SimpleCacheRateLimiter($cache);

        $first  = $limiter->hit('key', 2, 60);
        $second = $limiter->hit('key', 2, 60);

        $this->assertTrue($first->allowed);
        $this->assertSame(2, $first->limit);
        $this->assertSame(1, $first->remaining);
        $this->assertGreaterThan(0, $first->retryAfterSeconds);
        $this->assertGreaterThan(time(), $first->resetAt);
        $this->assertTrue($second->allowed);
        $this->assertSame(0, $second->remaining);
    }

    /**
     * Проверяем, что при превышении лимита доступ запрещается.
     */
    public function testExceedLimitDisallows(): void
    {
        $cache = new ArrayCache();

        $limiter = new SimpleCacheRateLimiter($cache);

        $limiter->hit('key', 1, 60);
        $result = $limiter->hit('key', 1, 60);

        $this->assertFalse($result->allowed);
        $this->assertSame(0, $result->remaining);
    }
}
