<?php

namespace DarkGhostHunter\RememberableQuery;

use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
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
     * @var string|null
     */
    protected ?string $cacheKey = null;

    /**
     * RememberableQuery constructor.
     *
     * @param  \Illuminate\Contracts\Cache\Repository $cache
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public function __construct(Repository $cache, $builder)
    {
        $this->cache = $cache;
        $this->builder = $builder;
    }

    /**
     * Remembers a Query by a given time
     *
     * @param  \DateTimeInterface|\DateInterval|int $ttl When to invalidate the query result
     * @param  string|null $cacheKey
     * @return $this
     */
    public function remember($ttl, string $cacheKey = null): RememberableQuery
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
    public function __call(string $method, array $arguments)
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
        return 'query|' .
            base64_encode(md5($this->builder->toSql() . implode('', $this->builder->getBindings()), true));
    }
}
