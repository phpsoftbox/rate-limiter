# RateLimiter

Компонент реализует fixed-window rate limiting.

## API

`RateLimiterInterface`:

- `hit(string $key, int $maxAttempts, int $decaySeconds): RateLimitResult` — регистрирует попытку; окно длиной
  `$decaySeconds` открывается первой попыткой;
- `attempts(string $key): int` — число попыток в текущем окне (0, если окна нет или оно истекло);
- `reset(string $key): void` — сбрасывает счётчик, например после успешного входа.

```php
$result = $limiter->hit($key, 5, 300);
if (!$result->allowed) {
    // 429, Retry-After: $result->retryAfterSeconds
}

if ($credentialsValid) {
    $limiter->reset($key);
}
```

## Время

Все лимитеры принимают необязательный PSR-20 `Psr\Clock\ClockInterface` (например, `PhpSoftBox\Clock\FrozenClock`
в тестах). Без него используется системное время `time()`.

```php
$limiter = new SimpleCacheRateLimiter($cache, $clock);
$limiter = new RedisRateLimiter($redis, $clock);
$limiter = new AtomicStoreRateLimiter($store, $clock);
```

В `RedisRateLimiter` часы влияют только на `resetAt`: длину окна отсчитывает TTL в Redis.

## Production

Для нескольких workers используйте атомарный storage:

```php
$limiter = new RedisRateLimiter($phpRedisClient);
```

`RedisRateLimiter` выполняет increment и установку TTL одним Lua script.
Поддерживается клиент PhpRedis (ext-redis): `Redis` или `RedisCluster` — тип проверяется в конструкторе.
Predis и другие клиенты (у Predis иная сигнатура `eval()`) подключаются собственной реализацией
`AtomicRateLimitStoreInterface` (`increment`, `attempts`, `reset`) и `AtomicStoreRateLimiter`.

`SimpleCacheRateLimiter` использует обычный PSR-16 `get/set`, поэтому подходит
только для development, тестов или гарантированно single-process окружения.

`RateLimitResult` содержит:

- `limit`;
- `remaining`;
- `retryAfterSeconds` — относительное ожидание;
- `resetAt` — абсолютный Unix timestamp.

## Ключи

`HashRateLimitKeyNormalizer` формирует PSR-16-safe ключ из namespace и SHA-256:

```php
$key = $normalizer->normalize($rawKey, 'node_api');
```

Raw credentials нельзя включать даже в исходный ключ. Используйте Node ID,
credential selector или необратимый hash безопасного идентификатора.
