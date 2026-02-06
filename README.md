# RateLimiter

Компонент реализует fixed-window rate limiting.

## Production

Для нескольких workers используйте атомарный storage:

```php
$limiter = new RedisRateLimiter($phpRedisClient);
```

`RedisRateLimiter` выполняет increment и установку TTL одним Lua script.
Альтернативный backend можно подключить через `AtomicRateLimitStoreInterface` и
`AtomicStoreRateLimiter`.

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
