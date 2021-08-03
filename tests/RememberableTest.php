<?php

namespace Tests;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use DarkGhostHunter\RememberableQuery\RememberableQuery;
use DarkGhostHunter\RememberableQuery\RememberableQueryServiceProvider;
use RuntimeException;

class RememberableTest extends TestCase
{
    use WithLaravelMigrations;
    use WithFaker;
    use DatabaseMigrations;

    protected function getPackageProviders($app): array
    {
        return [RememberableQueryServiceProvider::class];
    }

    protected function setUp() : void
    {
        $this->afterApplicationCreated(function () {
            $this->loadLaravelMigrations();

            for ($i = 0; $i < 10; ++$i) {
                User::make()->forceFill([
                    'email' => $this->faker->freeEmail,
                    'name' => $this->faker->name,
                    'password' => 'password',
                    'email_verified_at' => today(),
                ])->save();
            }

        });

        parent::setUp();
    }

    public function test_macro_returns_rememberable_query_instance(): void
    {
        static::assertInstanceOf(RememberableQuery::class, DB::table('users')->remember(60));
        static::assertInstanceOf(RememberableQuery::class, User::remember(60));

        static::assertInstanceOf(RememberableQuery::class,
            DB::table('users')->where('password')->remember(60)
        );
        static::assertInstanceOf(RememberableQuery::class,
            User::where('password', null)->remember(60)
        );
    }

    public function test_eloquent_builder_cached(): void
    {
        $id = User::inRandomOrder()->remember(60)->value('id');
        User::destroy($id);
        static::assertEquals($id, User::inRandomOrder()->remember(60)->value('id'));

        $id = User::inRandomOrder()->remember(30, 'customKey')->value('id');
        User::destroy($id);
        static::assertEquals($id, User::inRandomOrder()->remember(30, 'customKey')->value('id'));

        $id = User::inRandomOrder()->remember(30, 'differentKey')->value('id');
        User::destroy($id);
        static::assertNotEquals($id, User::inRandomOrder()->remember(30, 'customKey')->value('id'));
    }

    public function test_query_builder_cached(): void
    {
        $id = DB::table('users')->inRandomOrder()->remember(60)->value('id');
        DB::table('users')->delete($id);
        static::assertEquals($id, DB::table('users')->inRandomOrder()->remember(60)->value('id'));

        $id = DB::table('users')->inRandomOrder()->remember(30, 'customKey')->value('id');
        DB::table('users')->delete($id);
        static::assertEquals($id, DB::table('users')->inRandomOrder()->remember(30, 'customKey')->value('id'));

        $id = DB::table('users')->inRandomOrder()->remember(30, 'differentKey')->value('id');
        DB::table('users')->delete($id);
        static::assertNotEquals($id, DB::table('users')->inRandomOrder()->remember(30, 'customKey')->value('id'));
    }

    public function test_query_returns_saved_null_result(): void
    {
        $id = DB::table('users')->where('name', 'not-exist')->remember(60)->first();

        User::make()->forceFill([
            'name' => 'not-exists',
            'email' => $this->faker->freeEmail,
            'password' => 'password',
            'email_verified_at' => today(),
        ])->save();

        static::assertNull($result = DB::table('users')->where('name', 'not-exist')->remember(60)->first());
        static::assertEquals($result, $id);
    }

    public function test_forces_developer_to_set_as_before_last_method(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The `remember()` method call is not before query execution: [inRandomOrder] called.');

        DB::table('users')->remember(60, 'test')->inRandomOrder()->first();
    }

    public function test_idempotent_operations(): void
    {
        DB::table('users')->remember(60, 'test')->update(['name' => 'foo']);

        $this->assertDatabaseHas('users', ['name' => 'foo']);

        DB::table('users')->remember(60, 'test')->update(['name' => 'bar']);

        $this->assertDatabaseHas('users', ['name' => 'foo']);

        cache()->delete('test');

        DB::table('users')->remember(60, 'test')->update(['name' => 'bar']);

        $this->assertDatabaseMissing('users', ['name' => 'foo']);
        $this->assertDatabaseHas('users', ['name' => 'bar']);
    }

    public function test_doesnt_register_macros_if_collisions_with_name(): void
    {
        QueryBuilder::macro('remember', static function () : bool {
            return true;
        });

        EloquentBuilder::macro('remember', static function () : bool {
            return true;
        });

        $this->app->resolveProvider(RememberableQueryServiceProvider::class)->boot();

        static::assertTrue(QueryBuilder::remember());
        static::assertTrue(EloquentBuilder::remember());
    }
}
