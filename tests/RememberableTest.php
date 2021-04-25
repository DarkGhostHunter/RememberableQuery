<?php

namespace Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use DarkGhostHunter\RememberableQuery\RememberableQuery;
use DarkGhostHunter\RememberableQuery\RememberableQueryServiceProvider;

class RememberableTest extends TestCase
{
    use WithLaravelMigrations;
    use WithFaker;
    use DatabaseMigrations;

    protected function getPackageProviders($app)
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

    public function testMacroReturnsRememberableQueryInstance()
    {
        $this->assertInstanceOf(RememberableQuery::class, DB::table('users')->remember(60));
        $this->assertInstanceOf(RememberableQuery::class, User::remember(60));

        $this->assertInstanceOf(RememberableQuery::class,
            DB::table('users')->remember(60)->where('password', null)
        );
        $this->assertInstanceOf(RememberableQuery::class,
            User::remember(60)->where('password', null)
        );
    }

    public function testEloquentBuilderCached()
    {
        $id = User::inRandomOrder()->remember(60)->value('id');
        User::destroy($id);
        $this->assertEquals($id, User::inRandomOrder()->remember(60)->value('id'));

        $id = User::inRandomOrder()->remember(30, 'customKey')->value('id');
        User::destroy($id);
        $this->assertEquals($id, User::inRandomOrder()->remember(30, 'customKey')->value('id'));

        $id = User::inRandomOrder()->remember(30, 'differentKey')->value('id');
        User::destroy($id);
        $this->assertNotEquals($id, User::inRandomOrder()->remember(30, 'customKey')->value('id'));
    }

    public function testQueryBuilderCached()
    {
        $id = DB::table('users')->inRandomOrder()->remember(60)->value('id');
        DB::table('users')->delete($id);
        $this->assertEquals($id, DB::table('users')->inRandomOrder()->remember(60)->value('id'));

        $id = DB::table('users')->inRandomOrder()->remember(30, 'customKey')->value('id');
        DB::table('users')->delete($id);
        $this->assertEquals($id, DB::table('users')->inRandomOrder()->remember(30, 'customKey')->value('id'));

        $id = DB::table('users')->inRandomOrder()->remember(30, 'differentKey')->value('id');
        DB::table('users')->delete($id);
        $this->assertNotEquals($id, DB::table('users')->inRandomOrder()->remember(30, 'customKey')->value('id'));
    }

    public function testQueryReturnsSavedNullResult()
    {
        $id = DB::table('users')->remember(60)->where('name', 'not-exist')->first();

        User::make()->forceFill([
            'name' => 'not-exists',
            'email' => $this->faker->freeEmail,
            'password' => 'password',
            'email_verified_at' => today(),
        ])->save();

        $this->assertNull($result = DB::table('users')->remember(60)->where('name', 'not-exist')->first());
        $this->assertEquals($result, $id);
    }
}
