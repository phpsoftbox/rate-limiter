<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

interface AtomicRateLimitStoreInterface
{
    /**
     * Атомарно увеличивает счётчик ключа; окно длиной $decaySeconds открывается первой попыткой.
     */
    public function increment(string $key, int $decaySeconds): RateLimitCounter;

    /**
     * Возвращает текущее значение счётчика (0, если окна нет или оно истекло).
     */
    public function attempts(string $key): int;

    /**
     * Удаляет счётчик ключа.
     */
    public function reset(string $key): void;
}
