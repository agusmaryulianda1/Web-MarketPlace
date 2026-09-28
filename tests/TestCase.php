<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function beforeRefreshingDatabase()
    {
        $expected = [
            'app.env' => 'testing',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ];

        foreach ($expected as $key => $value) {
            $this->assertSame(
                $value,
                config($key),
                "Unsafe test database configuration: [{$key}] must be [{$value}]. Database migration was blocked.",
            );
        }
    }
}
