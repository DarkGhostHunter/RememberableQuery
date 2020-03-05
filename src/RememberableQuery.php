<?php

namespace DarkGhostHunter\RememberableQuery;

use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class RememberableQuery
{
    use ForwardsCalls;

    /**
     * The Application Cache repository
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Query Builder instance
     *
     * @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    protected $builder;

    /**
     * Seconds the query results should live
     *
     * @var \DateTimeInterface|\DateInterval|int
     */
    protected $ttl = 60;

    /**
     * The Cache Key to use
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * RememberableQuery constructor.
     *
     * @param  \Illuminate\Contracts\Cache\Repository $cache
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function __construct(Cache $cache, $builder)
    {
        $this->cache = $cache;
        $this->builder = $builder;
    }

    /**
     * Returns the Builder instance
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function builder()
    {
        return $this->builder;
    }

    /**
     * Remembers a Query by a given time
     *
     * @param  \DateTimeInterface|\DateInterval|int $ttl When to invalidate the query result
     * @param  string|null $cacheKey
     * @return $this
     */
    public function remember($ttl, string $cacheKey = null)
    {
        $this->ttl = $ttl;

        $this->cacheKey = $cacheKey;

        return $this;
    }

    /**
     * Dynamically call the query builder until a result is expected
     *
     * @param  string $method
     * @param  array $arguments
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function __call($method, $arguments)
    {
        // First, get the Cache Key we will work with.
        $key = $this->cacheKey();

        // Let's ask first if the Cache has a result by the key. If it does, return it.
        if ($cachedResult = $this->cache->get($key)) {
            return $cachedResult;
        }

        // Since we don't have any result, let's call the Query Builder.
        $result = $this->forwardCallTo($this->builder, $method, $arguments);

        // If the call returns the same Builder instance, just return this to keep building the Query.
        if ($result instanceof QueryBuilder || $result instanceof EloquentBuilder) {
            return $this;
        }

        // Otherwise, we will have a result from the Query. In that case, we will save it before returning.
        $this->cache->put($key, $result, $this->ttl);

        return $result;
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
        return 'query|' . md5($this->builder->toSql() . implode('', $this->builder->getBindings()));
    }
}
