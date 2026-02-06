<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

use InvalidArgumentException;

final readonly class RateLimitCounter
{
    public function __construct(
        public int $attempts,
        public int $retryAfterSeconds,
    ) {
        if ($this->attempts < 1) {
            throw new InvalidArgumentException('Rate limit attempts must be greater than zero.');
        }

        if ($this->retryAfterSeconds < 1) {
            throw new InvalidArgumentException('Retry-after duration must be greater than zero.');
        }
    }
}
