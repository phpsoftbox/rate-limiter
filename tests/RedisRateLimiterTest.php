<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter\Tests;

use PhpSoftBox\RateLimiter\AtomicStoreRateLimiter;
use PhpSoftBox\RateLimiter\RedisAtomicRateLimitStore;
use PhpSoftBox\RateLimiter\RedisRateLimiter;
use PhpSoftBox\RateLimiter\Tests\Fixtures\FakeRedis;
use PhpSoftBox\RateLimiter\Tests\Fixtures\FixedClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RedisRateLimiter::class)]
#[CoversClass(AtomicStoreRateLimiter::class)]
#[CoversClass(RedisAtomicRateLimitStore::class)]
#[CoversMethod(RedisRateLimiter::class, 'hit')]
#[CoversMethod(RedisRateLimiter::class, 'attempts')]
#[CoversMethod(RedisRateLimiter::class, 'reset')]
final class RedisRateLimiterTest extends TestCase
{
    /**
     * Проверяем, что счётчик увеличивается одним Lua-скриптом, а resetAt считается от инжектированных часов.
     *
     * @see RedisRateLimiter::hit()
     * @see RedisAtomicRateLimitStore::increment()
     */
    #[Test]
    public function usesAtomicLuaCounterAndReturnsAbsoluteReset(): void
    {
        $redis = new FakeRedis();

        $limiter = new RedisRateLimiter($redis, new FixedClock(1_000));

        $first  = $limiter->hit('rate_limit.key', 1, 60);
        $second = $limiter->hit('rate_limit.key', 1, 60);

        self::assertTrue($first->allowed);
        self::assertFalse($second->allowed);
        self::assertSame(0, $second->remaining);
        self::assertSame(60, $second->retryAfterSeconds);
        self::assertSame(1_060, $second->resetAt);

        // Оба hit — один EVAL с PhpRedis-сигнатурой (script, [keys..., args...], numKeys).
        self::assertCount(2, $redis->evalCalls);
        self::assertStringContainsString("redis.call('INCR'", $redis->evalCalls[0]['script']);
        self::assertSame(['rate_limit.key', 60], $redis->evalCalls[0]['args']);
        self::assertSame(1, $redis->evalCalls[0]['keys']);
    }

    /**
     * Проверяем, что attempts() возвращает текущее значение счётчика.
     *
     * @see RedisRateLimiter::attempts()
     * @see RedisAtomicRateLimitStore::attempts()
     */
    #[Test]
    public function attemptsReturnsCurrentCounter(): void
    {
        $limiter = new RedisRateLimiter(new FakeRedis());

        $limiter->hit('login', 5, 60);
        $limiter->hit('login', 5, 60);

        self::assertSame(2, $limiter->attempts('login'));
    }

    /**
     * Проверяем, что attempts() для ключа без счётчика возвращает 0.
     *
     * @see RedisRateLimiter::attempts()
     */
    #[Test]
    public function attemptsReturnsZeroForUnknownKey(): void
    {
        $limiter = new RedisRateLimiter(new FakeRedis());

        self::assertSame(0, $limiter->attempts('missing'));
    }

    /**
     * Проверяем, что reset() удаляет счётчик и следующая попытка открывает новое окно.
     *
     * @see RedisRateLimiter::reset()
     * @see RedisAtomicRateLimitStore::reset()
     */
    #[Test]
    public function resetClearsCounter(): void
    {
        $limiter = new RedisRateLimiter(new FakeRedis());

        $limiter->hit('login', 1, 60);
        $limiter->reset('login');

        self::assertSame(0, $limiter->attempts('login'));
        self::assertTrue($limiter->hit('login', 1, 60)->allowed);
    }
}
