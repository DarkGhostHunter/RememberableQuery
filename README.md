# Package superseeded by [Laragear/CacheQuery](https://github.com/Laragear/CacheQuery)

---

# Rememberable Queries

Remember your Query results using only one method. Yes, only one.

```php
Articles::latest('published_at')->take(10)->remember()->get();
```

## Requirements

* PHP 8.0
* Laravel 8.x

## Installation

You can install the package via composer:

```bash
composer require darkghosthunter/rememberable-query
```

## Usage

Just use the `remember()` method to remember a Query result **before the execution**. That's it. The method automatically remembers the result for 60 seconds.

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

In some scenarios, using the default cache of your application may be detrimental compared to the database performance, or may conflict with other keys. You can use any other Cache Store by setting a third parameter, or a named parameter.

```php
Article::latest('published_at')->take(10)->remember(store: 'redis')->get();
```

### Cache Lock (data races)

On multiple processes, the Query may be executed multiple times until the first process is able to store the result in the cache, specially when these take more than 1 second. To avoid this, set the `wait` parameter with the number of seconds to hold the lock acquired.

```php
Article::latest('published_at')->take(200)->remember(wait: 5)->get();
```

The first process will acquire the lock for the given seconds, execute the query and store the result. The next processes will wait until the cache data is available to retrieve the result from there.

> If you need to use this across multiple processes, use the [cache lock](https://laravel.com/docs/cache#managing-locks-across-processes) directly.

### Idempotent queries

While the reason behind remembering a Query is to cache the data retrieved from a database, you can use this to your advantage to create [idempotent](https://en.wikipedia.org/wiki/Idempotence) queries.

For example, you can make this query only execute once every day for a given user ID.

```php
$key = auth()->user()->getAuthIdentifier();

Article::whereKey(54)->remember(now()->addHour(), "query|user:$key")->increment('unique_views');
```

Subsequent executions of this query won't be executed at all until the cache expires, so in the above example we have surprisingly created a "unique views" mechanic.

## Operations are **NOT** commutative

Altering the Builder methods order will change the auto-generated cache key hash. Even if they are _visually_ the same, the order of statements makes the hash completely different.

For example, given two similar queries in different parts of the application, these both will **not** share the same cached result:

```php
User::whereName('Joe')->whereAge(20)->remember()->first();
// Cache key: "query|/XreUO1yaZ4BzH2W6LtBSA=="

User::whereAge(20)->whereName('Joe')->remember()->first();
// Cache key: "query|muDJevbVppCsTFcdeZBxsA=="
```

To ensure you're hitting the same cache on similar queries, use a [custom cache key](#custom-cache-key). With this, all queries using the same key will share the same cached result:

```php
User::whereName('Joe')->whereAge(20)->remember(60, 'query|find_joe')->first();
User::whereAge(20)->whereName('Joe')->remember(60, 'query|find_joe')->first();
```

This will allow you to even retrieve the data outside the query, by just asking directly to the cache.

```php
$joe = Cache::get('query|find_joe');
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
