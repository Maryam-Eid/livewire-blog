<?php

use Illuminate\Support\Facades\DB;

test('the test suite uses sqlite in memory', function () {
    expect(config('app.env'))->toBe('testing');
    expect(config('database.default'))->toBe('sqlite');
    expect(config('database.connections.sqlite.database'))->toBe(':memory:');
    expect(DB::connection()->getDatabaseName())->toBe(':memory:');
});
