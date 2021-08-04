<?php

namespace DarkGhostHunter\RememberableQuery;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\LockProvider as LockProviderAlias;
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
     * The cache key to use to remember.
     *
     * @var string
     */
    protected string $cacheKey;

    /**
     * RememberableQuery constructor.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     * @param  int|\DateTimeInterface|\DateInterval  $ttl
     * @param  string|null  $cacheKey
     * @param  string|null  $store
     * @param  int  $wait
     */
    public function __construct(
        Factory $cache,
        protected Builder|EloquentBuilder $builder,
        protected int|DateTimeInterface|DateInterval $ttl,
        ?string $cacheKey = null,
        ?string $store = null,
        protected int $wait = 0
    ) {
        $this->cacheKey = $cacheKey ?? $this->cacheKeyHash();
        $this->cache = $cache->store($store);
    }

    /**
     * Returns the auto-generated cache key
     *
     * @return string
     */
    public function cacheKeyHash(): string
    {
        return 'query|'.base64_encode(
            md5($this->builder->toSql().implode('', $this->builder->getBindings()), true)
        );
    }

    /**
     * Dynamically call the query builder until a result is expected
     *
     * @param  string  $method
     * @param  array  $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->cache->remember($this->cacheKey, $this->ttl, function () use ($method, $arguments) {
            return $this->wait
                ? $this->getLockResult($method, $arguments)
                : $this->getResult($method, $arguments);
        });
    }

    /**
     * Returns the results from a lock callback.
     *
     * @param  string  $method
     * @param  array  $arguments
     *
     * @return mixed
     */
    protected function getLockResult(string $method, array $arguments): mixed
    {
        return $this->cache
            ->lock($this->cacheKey, $this->wait)
            ->block($this->wait, fn() => $this->getResult($method, $arguments));
    }

    /**
     * Forwards the call to the builder and retrieves the result.
     *
     * @param  string  $method
     * @param  array  $arguments
     *
     * @return mixed
     */
    protected function getResult(string $method, array $arguments): mixed
    {
        $result = $this->forwardCallTo($this->builder, $method, $arguments);

        // Force the developer to use this as before-last method in the query builder.
        if ($result instanceof Builder || $result instanceof EloquentBuilder) {
            throw new RuntimeException("The `remember()` method call is not before query execution: [$method] called.");
        }

        return $result;
    }
}
