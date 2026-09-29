<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter\Tests\Fixtures;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final class FixedClock implements ClockInterface
{
    public function __construct(
        public int $timestamp,
    ) {
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('@' . $this->timestamp);
    }
}
