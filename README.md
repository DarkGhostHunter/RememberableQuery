![Patrick Perkins - Unslash (UL) #ETRPjvb0KM0](https://images.unsplash.com/photo-1503551723145-6c040742065b?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80&q=80&w=1280&h=400)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darkghosthunter/rememberable-query.svg?style=flat-square)](https://packagist.org/packages/darkghosthunter/rememberable-query) [![License](https://poser.pugx.org/darkghosthunter/rememberable-query/license)](https://packagist.org/packages/darkghosthunter/rememberable-query)
![](https://img.shields.io/packagist/php-v/darkghosthunter/rememberable-query.svg)
 ![](https://github.com/DarkGhostHunter/RememberableQuery/workflows/PHP%20Composer/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/RememberableQuery/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/RememberableQuery?branch=master)

# Rememberable Queries

Remember your Query results using only one method. Yes, only one.

```php
Articles::latest('published_at')->take(10)->remember()->get();
```

## Requirements

* PHP 7.4 or PHP 8.0
* Laravel 7.x or 8.x
* Neurons with synapse enabled

## Installation

You can install the package via composer:

```bash
composer require darkghosthunter/rememberable-query
```

## Usage

Just use the `remember()` method to remember a Query result **before the execution**. That's it.

```php
use Illuminate\Support\Facades\DB;
use App\Models\Article;

$database = DB::table('articles')->latest('published_at')->take(10)->remember()->get();

$eloquent = Article::latest('published_at')->take(10)->remember()->get();
```

The next time you call the **same** query, the result will be retrieved from the cache instead of running the SQL statement in the database, even if the result is `null` or `false`.

> The `remember()` will throw an error if you build a query instead of executing it.

### Time-to-live

By default, queries are remembered by 60 seconds, but you're free to use any length, `Datetime`, `DateInterval` or Carbon instance.

```php
DB::table('articles')->latest('published_at')->take(10)->remember(60 * 60)->get();

Article::latest('published_at')->take(10)->remember(now()->addHour())->get();
```

### Custom Cache Key

The auto-generated cache key is an BASE64-MD5 hash of the SQL query and its bindings, which avoids any collision with other queries while keeping the cache key short. 

You can use any string as you want, but is recommended to append `query|` to avoid conflicts with other cache keys in your application.

```php
Article::latest('published_at')->take(10)->remember(30, 'query|latest_articles')->get();
```

Alternatively, you can use an [custom Cache Store](#custom-cache-store) for remembering queries.

### Custom Cache Store

In some scenarios, using the default cache of your application may be detrimental compared to the database performance, or may conflict with other keys. You can use any other Cache Store by telling the Service Container to pass it to the `RememberableQuery` class (preferably) in your `AppServiceProvider`.

```php
public function register()
{
    $this->app
        ->when(\DarkGhostHunter\RememberableQuery\RememberableQuery::class)
        ->needs(\Illuminate\Contracts\Cache\Repository::class)
        ->give(static function (): \Illuminate\Contracts\Cache\Repository {
            return cache()->store('redis');
        });
    
    // ...
}
```

### Idempotent queries

While the reason behind remembering a Query is to cache the data retrieved from a database, you can use this to your advantage to create [idempotent](https://en.wikipedia.org/wiki/Idempotence) queries.

For example, you can make this query only execute once every day for a given IP, as it's the time the cache expires.

```php
$ttl = now()->addHour();
$key = 'unique_visitor:192.168.0.54';

Article::whereKey(54)->remember($ttl, $key)->increment('unique_views');
```

Subsequent executions of this query won't be executed at all until the cache expires, so in the above example we have surprisingly created a "unique views" mechanic. 

## Operations are **NOT** commutative

Altering the Builder methods order will change the auto-generated cache key hash. Even if they are _visually_ the same, the order of statements makes the hash different.

For example, given two similar queries in different parts of the application, these both will **not** share the same cached result:

```php
User::whereName('Joe')->whereAge(20)->remember()->first();
// Cache key: "query|/XreUO1yaZ4BzH2W6LtBSA=="

User::whereAge(20)->whereName('Joe')->remember()->first();
// Cache key: "query|muDJevbVppCsTFcdeZBxsA=="
```

You can use a [custom cache key](#custom-cache-key) to avoid this problem, as both queries results will share the same cached result:

```php
User::whereName('Joe')->whereAge(20)->remember(60, 'query|find_joe')->first();
User::whereAge(20)->whereName('Joe')->remember(60, 'query|find_joe')->first();
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
