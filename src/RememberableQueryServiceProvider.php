<?php

namespace DarkGhostHunter\RememberableQuery;

use DateInterval;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\ServiceProvider;

class RememberableQueryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $function = function (
            int|DateTimeInterface|DateInterval $ttl = 60,
            string $cacheKey = null,
            string $store = null
        ): RememberableQuery {
            return new RememberableQuery(resolve('cache'), $this, $ttl, $cacheKey, $store);
        };

        if (!QueryBuilder::hasMacro('remember')) {
            QueryBuilder::macro('remember', $function);
        }

        if (!EloquentBuilder::hasGlobalMacro('remember')) {
            EloquentBuilder::macro('remember', $function);
        }
    }
}
