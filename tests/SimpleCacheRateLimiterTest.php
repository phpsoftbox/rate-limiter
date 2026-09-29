<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter\Tests;

use PhpSoftBox\RateLimiter\SimpleCacheRateLimiter;
use PhpSoftBox\RateLimiter\Tests\Fixtures\ArrayCache;
use PhpSoftBox\RateLimiter\Tests\Fixtures\FixedClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function time;

#[CoversClass(SimpleCacheRateLimiter::class)]
#[CoversMethod(SimpleCacheRateLimiter::class, 'hit')]
#[CoversMethod(SimpleCacheRateLimiter::class, 'attempts')]
#[CoversMethod(SimpleCacheRateLimiter::class, 'reset')]
final class SimpleCacheRateLimiterTest extends TestCase
{
    /**
     * Проверяем, что лимитер считает попытки и возвращает оставшееся число.
     *
     * @see SimpleCacheRateLimiter::hit()
     */
    #[Test]
    public function countsAttempts(): void
    {
        $limiter = new SimpleCacheRateLimiter(new ArrayCache());

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
     *
     * @see SimpleCacheRateLimiter::hit()
     */
    #[Test]
    public function exceedLimitDisallows(): void
    {
        $limiter = new SimpleCacheRateLimiter(new ArrayCache());

        $limiter->hit('key', 1, 60);
        $result = $limiter->hit('key', 1, 60);

        $this->assertFalse($result->allowed);
        $this->assertSame(0, $result->remaining);
    }

    /**
     * Проверяем, что окно и resetAt считаются от инжектированных часов.
     *
     * @see SimpleCacheRateLimiter::hit()
     */
    #[Test]
    public function usesInjectedClock(): void
    {
        $clock = new FixedClock(1_000);

        $limiter = new SimpleCacheRateLimiter(new ArrayCache(), $clock);

        $limiter->hit('key', 5, 60);
        $clock->timestamp = 1_020;
        $result           = $limiter->hit('key', 5, 60);

        $this->assertSame(1_060, $result->resetAt);
        $this->assertSame(40, $result->retryAfterSeconds);
    }

    /**
     * Проверяем, что по истечении окна (по часам лимитера) счётчик начинается заново.
     *
     * @see SimpleCacheRateLimiter::hit()
     */
    #[Test]
    public function expiredWindowStartsOver(): void
    {
        $clock = new FixedClock(1_000);

        $limiter = new SimpleCacheRateLimiter(new ArrayCache(), $clock);

        $limiter->hit('key', 1, 60);
        $clock->timestamp = 1_060;
        $result           = $limiter->hit('key', 1, 60);

        $this->assertTrue($result->allowed);
        $this->assertSame(1_120, $result->resetAt);
    }

    /**
     * Проверяем, что attempts() возвращает число попыток в активном окне.
     *
     * @see SimpleCacheRateLimiter::attempts()
     */
    #[Test]
    public function attemptsReturnsCountInActiveWindow(): void
    {
        $limiter = new SimpleCacheRateLimiter(new ArrayCache(), new FixedClock(1_000));

        $limiter->hit('key', 5, 60);
        $limiter->hit('key', 5, 60);

        $this->assertSame(2, $limiter->attempts('key'));
    }

    /**
     * Проверяем, что attempts() не учитывает истёкшее окно.
     *
     * @see SimpleCacheRateLimiter::attempts()
     */
    #[Test]
    public function attemptsIgnoresExpiredWindow(): void
    {
        $clock = new FixedClock(1_000);

        $limiter = new SimpleCacheRateLimiter(new ArrayCache(), $clock);

        $limiter->hit('key', 5, 60);
        $clock->timestamp = 1_060;

        $this->assertSame(0, $limiter->attempts('key'));
    }

    /**
     * Проверяем, что reset() сбрасывает счётчик и снимает блокировку.
     *
     * @see SimpleCacheRateLimiter::reset()
     */
    #[Test]
    public function resetClearsCounter(): void
    {
        $limiter = new SimpleCacheRateLimiter(new ArrayCache(), new FixedClock(1_000));

        $limiter->hit('key', 1, 60);
        $limiter->reset('key');

        $this->assertSame(0, $limiter->attempts('key'));
        $this->assertTrue($limiter->hit('key', 1, 60)->allowed);
    }
}
