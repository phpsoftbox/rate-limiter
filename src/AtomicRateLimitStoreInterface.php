<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

interface AtomicRateLimitStoreInterface
{
    public function increment(string $key, int $decaySeconds): RateLimitCounter;
}
