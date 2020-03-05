<?php

namespace DarkGhostHunter\RememberableQuery;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class RememberableQueryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        QueryBuilder::macro('remember', function ($ttl = 60, string $cacheKey = null) {
            return app(RememberableQuery::class, ['builder' => $this ])->remember($ttl, $cacheKey);
        });

        EloquentBuilder::macro('remember', function ($ttl = 60, string $cacheKey = null) {
            return app(RememberableQuery::class, ['builder' => $this ])->remember($ttl, $cacheKey);
        });
    }
}
