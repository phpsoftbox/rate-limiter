<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter;

interface RateLimiterInterface
{
    /**
     * Регистрирует попытку в fixed-window окне ключа и возвращает состояние лимита после неё.
     */
    public function hit(string $key, int $maxAttempts, int $decaySeconds): RateLimitResult;

    /**
     * Возвращает число попыток в текущем окне ключа (0, если окна нет или оно истекло).
     */
    public function attempts(string $key): int;

    /**
     * Сбрасывает счётчик ключа (например, после успешного входа).
     */
    public function reset(string $key): void;
}
