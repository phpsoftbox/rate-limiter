<?php

declare(strict_types=1);

namespace PhpSoftBox\RateLimiter\Tests\Fixtures;

use Redis;

use function array_key_exists;
use function is_array;

/**
 * PhpRedis-клиент без соединения: эмулирует Lua-скрипт лимитера, GET и DEL в памяти.
 */
final class FakeRedis extends Redis
{
    /** @var array<string, int> */
    public array $counters = [];

    /** @var list<array{script: string, args: array<mixed>, keys: int}> */
    public array $evalCalls = [];

    public int $ttl = 60;

    public function eval(string $script, array $args = [], int $num_keys = 0): mixed
    {
        $this->evalCalls[] = ['script' => $script, 'args' => $args, 'keys' => $num_keys];

        $key                  = (string) $args[0];
        $this->counters[$key] = ($this->counters[$key] ?? 0) + 1;

        return [$this->counters[$key], $this->ttl];
    }

    public function get(string $key): mixed
    {
        return array_key_exists($key, $this->counters) ? (string) $this->counters[$key] : false;
    }

    public function del(array|string $key, string ...$other_keys): Redis|int|false
    {
        $deleted = 0;
        foreach ([...(is_array($key) ? $key : [$key]), ...$other_keys] as $name) {
            if (array_key_exists($name, $this->counters)) {
                unset($this->counters[$name]);
                ++$deleted;
            }
        }

        return $deleted;
    }
}
