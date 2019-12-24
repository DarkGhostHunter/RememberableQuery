<?php

namespace DarkGhostHunter\RememberableQuery;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class RememberableQueryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        QueryBuilder::macro('remember', function (int $ttl, string $cacheKey = null) {
            return app(RememberableQuery::class)->setBuilder($this)->remember($ttl, $cacheKey);
        });

        EloquentBuilder::macro('remember', function (int $ttl, string $cacheKey = null) {
            return app(RememberableQuery::class)->setBuilder($this)->remember($ttl, $cacheKey);
        });
    }
}
