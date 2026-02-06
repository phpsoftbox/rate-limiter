<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

use InvalidArgumentException;

final readonly class RateLimitResult
{
    public function __construct(
        public bool $allowed,
        public int $limit,
        public int $remaining,
        public int $retryAfterSeconds,
        public int $resetAt,
    ) {
        if ($this->limit < 1) {
            throw new InvalidArgumentException('Rate limit must be greater than zero.');
        }

        if ($this->remaining < 0 || $this->remaining > $this->limit) {
            throw new InvalidArgumentException('Remaining attempts must be between zero and the rate limit.');
        }

        if ($this->retryAfterSeconds < 1 || $this->resetAt < 1) {
            throw new InvalidArgumentException('Rate limit reset metadata must be positive.');
        }
    }
}
