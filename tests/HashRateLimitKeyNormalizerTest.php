<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter\Tests;

use InvalidArgumentException;
use PhpSoftBox\RateLimiter\HashRateLimitKeyNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HashRateLimitKeyNormalizer::class)]
#[CoversMethod(HashRateLimitKeyNormalizer::class, 'normalize')]
final class HashRateLimitKeyNormalizerTest extends TestCase
{
    /**
     * Проверяем, что ключ состоит из namespace и SHA-256 и безопасен для PSR-16.
     *
     * @see HashRateLimitKeyNormalizer::normalize()
     */
    #[Test]
    public function producesPsr16SafeNamespacedKey(): void
    {
        $key = new HashRateLimitKeyNormalizer()->normalize('127.0.0.1|/api/node/v1', 'node_api');

        self::assertMatchesRegularExpression('/^node_api\.[a-f0-9]{64}$/D', $key);
        self::assertStringNotContainsString('/', $key);
    }

    /**
     * Проверяем, что namespace с недопустимыми для PSR-16 символами отклоняется.
     *
     * @see HashRateLimitKeyNormalizer::normalize()
     */
    #[Test]
    public function rejectsUnsafeNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HashRateLimitKeyNormalizer()->normalize('key', 'node/api');
    }
}
