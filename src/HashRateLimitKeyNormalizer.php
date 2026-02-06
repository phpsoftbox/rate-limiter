<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

use InvalidArgumentException;

use function hash;
use function preg_match;
use function trim;

final readonly class HashRateLimitKeyNormalizer implements RateLimitKeyNormalizerInterface
{
    public function normalize(string $key, string $namespace = 'rate_limit'): string
    {
        $namespace = trim($namespace);
        if ($namespace === '' || preg_match('/^[A-Za-z0-9_.-]+$/D', $namespace) !== 1) {
            throw new InvalidArgumentException('Rate limit namespace must be PSR-16 key safe.');
        }

        return $namespace . '.' . hash('sha256', $key);
    }
}
