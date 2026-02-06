<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

interface RateLimitKeyNormalizerInterface
{
    public function normalize(string $key, string $namespace = 'rate_limit'): string;
}
