<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter\Tests;

use InvalidArgumentException;
use PhpSoftBox\RateLimiter\HashRateLimitKeyNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HashRateLimitKeyNormalizerTest extends TestCase
{
    #[Test]
    public function producesPsr16SafeNamespacedKey(): void
    {
        $key = new HashRateLimitKeyNormalizer()->normalize('127.0.0.1|/api/node/v1', 'node_api');

        self::assertMatchesRegularExpression('/^node_api\.[a-f0-9]{64}$/D', $key);
        self::assertStringNotContainsString('/', $key);
    }

    #[Test]
    public function rejectsUnsafeNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HashRateLimitKeyNormalizer()->normalize('key', 'node/api');
    }
}
