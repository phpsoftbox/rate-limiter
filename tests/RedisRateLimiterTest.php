<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter\Tests;

use PhpSoftBox\RateLimiter\RedisRateLimiter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RedisRateLimiterTest extends TestCase
{
    #[Test]
    public function usesAtomicLuaCounterAndReturnsAbsoluteReset(): void
    {
        $redis = new class () {
            public int $attempts = 0;
            public int $calls    = 0;

            public function eval(string $script, array $arguments, int $keyCount): array
            {
                ++$this->calls;
                ++$this->attempts;
                TestCase::assertStringContainsString("redis.call('INCR'", $script);
                TestCase::assertSame(['rate_limit.key', 60], $arguments);
                TestCase::assertSame(1, $keyCount);

                return [$this->attempts, 60];
            }
        };

        $limiter = new RedisRateLimiter($redis, static fn (): int => 1_000);

        $first  = $limiter->hit('rate_limit.key', 1, 60);
        $second = $limiter->hit('rate_limit.key', 1, 60);

        self::assertTrue($first->allowed);
        self::assertFalse($second->allowed);
        self::assertSame(0, $second->remaining);
        self::assertSame(60, $second->retryAfterSeconds);
        self::assertSame(1_060, $second->resetAt);
        self::assertSame(2, $redis->calls);
    }
}
