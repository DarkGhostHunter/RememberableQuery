<?php

namespace DarkGhostHunter\RememberableQuery;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Traits\ForwardsCalls;
use RuntimeException;

class RememberableQuery
{
    use ForwardsCalls;

    /**
     * The Application Cache repository
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected Repository $cache;

    /**
     * RememberableQuery constructor.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     * @param  int|\DateTimeInterface|\DateInterval  $ttl
     * @param  string|null  $cacheKey
     * @param  string|null  $store
     */
    public function __construct(
        Factory $cache,
        protected Builder|EloquentBuilder $builder,
        protected int|DateTimeInterface|DateInterval $ttl,
        protected ?string $cacheKey = null,
        ?string $store = null
        )
    {
        $this->cache = $cache->store($store);
    }

    /**
     * Returns the Cache Key to work with.
     *
     * @return string
     */
    protected function cacheKey() : string
    {
        return $this->cacheKey ?? $this->cacheKeyHash();
    }

    /**
     * Returns the auto-generated cache key
     *
     * @return string
     */
    public function cacheKeyHash() : string
    {
        return 'query|' . base64_encode(
            md5($this->builder->toSql() . implode('', $this->builder->getBindings()), true)
        );
    }

    /**
     * Dynamically call the query builder until a result is expected
     *
     * @param  string $method
     * @param  array $arguments
     * @return mixed
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function __call(string $method, array $arguments): mixed
    {
        // First, get the Cache Key we will work with.
        $key = $this->cacheKey();

        // Let's ask first if the Cache has a result by the key. If it does, return it.
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        // Since we don't have any result, get it from the Query Builder.
        $result = $this->forwardCallTo($this->builder, $method, $arguments);

        // Force the developer to use this as before-last method in the query builder.
        if ($result instanceof Builder || $result instanceof EloquentBuilder) {
            throw new RuntimeException(
                "The `remember()` method call is not before query execution: [$method] called."
            );
        }

        // Save the result before returning it.
        $this->cache->put($key, $result, $this->ttl);

        return $result;
    }
}
