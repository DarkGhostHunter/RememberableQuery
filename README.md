![Patrick Perkins - Unslash (UL) #ETRPjvb0KM0](https://images.unsplash.com/photo-1503551723145-6c040742065b?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80&q=80&w=1280&h=400)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darkghosthunter/rememberable-query.svg?style=flat-square)](https://packagist.org/packages/darkghosthunter/rememberable-query) [![License](https://poser.pugx.org/darkghosthunter/rememberable-query/license)](https://packagist.org/packages/darkghosthunter/rememberable-query)
![](https://img.shields.io/packagist/php-v/darkghosthunter/rememberable-query.svg)
 ![](https://github.com/DarkGhostHunter/RememberableQuery/workflows/PHP%20Composer/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/RememberableQuery/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/RememberableQuery?branch=master)

# Rememberable Queries

Remember your Query results using only one method. Yes, only one.

    User::latest()->where('name', 'Joe')->remember()->get();

## Requirements

* PHP 7.2 or latest
* Laravel 5.8|6.0
* A functioning brain

## Installation

You can install the package via composer:

```bash
composer require darkghosthunter/rememberable-query
```

## Usage

Just use the `remember()` method to remember a Query result. That's it.

```php
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User;

$userA = DB::table('users')->remember()->where('name', 'Joe')->first();

$userB = User::where('name', 'Joe')->remember()->first();
```

The next time you call the **same** query, the result will be retrieved from the cache instead of connecting to Database. 

> If the result is `null` or `false`, it won't be remembered, which mimics the Cache behaviour on these values.

### Time-to-live

By default, queries are remembered by 60 seconds, but you're free to use any length, or Datetime or Carbon instance.

```php
User::where('name', 'Joe')->remember(today()->addHour())->first();
```

### Custom Cache Key

By default, the cache key is an MD5 hash of the SQL query and bindings, which avoids any collision with other queries. You can use any string, but is recommended to append `query|myCustomKey` to avoid conflicts with other cache keys.

```php
User::where('name', 'Joe')->remember(30, 'query|find_joe')->first();
```


### When two Builders are not the same

Consider that altering the Builder methods order may change the automatic cache key generation, even if they are practically the same. For example:

```php
<?php

DB::table('users')->remember()->whereName('Joe')->whereAge(20)->first();
// "query|fecc2c1bb6396e485d94eede60532937"

DB::table('users')->remember()->whereAge(20)->whereName('Joe')->first();
// "query|3ac5eba7cd0ef6151481bdfe46f6c22f"
```

If you plan to _remember_ the same query on different parts of your application, it's recommended to set manually the same Cache Key to avoid using different cache keys. 

### Custom Cache

In some scenarios, using the default cache of your application may be detrimental compared to the database performance. You can use any other Cache by telling the Service Container to pass it to the `RememberableQuery` class (preferably) in your `AppServiceProvider`.

```php
public function boot()
{
    $this->app
        ->when(\DarkGhostHunter\RememberableQuery\RememberableQuery::class)
        ->needs(\Illuminate\Contracts\Cache\Repository::class)
        ->give(function () {
            return cache()->store('redis');
        });
    
    // ...
}
```


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
