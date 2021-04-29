<?php

namespace DarkGhostHunter\RememberableQuery;

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
        if (! QueryBuilder::hasMacro('remember')) {
            QueryBuilder::macro(
                'remember',
                function ($ttl = 60, string $cacheKey = null): RememberableQuery {
                    return app(RememberableQuery::class, ['builder' => $this])->remember($ttl, $cacheKey);
                }
            );
        }

        if (! EloquentBuilder::hasGlobalMacro('remember')) {
            EloquentBuilder::macro(
                'remember',
                function ($ttl = 60, string $cacheKey = null): RememberableQuery {
                    return app(RememberableQuery::class, ['builder' => $this])->remember($ttl, $cacheKey);
                }
            );
        }
    }
}
