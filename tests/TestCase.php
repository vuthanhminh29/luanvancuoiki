<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUpTraits(): array
    {
        $this->guardAgainstDestructiveTestingDatabase();

        return parent::setUpTraits();
    }

    private function guardAgainstDestructiveTestingDatabase(): void
    {
        $uses = class_uses_recursive(static::class);
        $usesDestructiveDatabaseTrait = isset($uses[RefreshDatabase::class])
            || isset($uses[DatabaseMigrations::class])
            || isset($uses[DatabaseTruncation::class]);

        if (! $usesDestructiveDatabaseTrait) {
            return;
        }

        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}", []);
        $driver = (string) ($connection['driver'] ?? '');
        $database = (string) ($connection['database'] ?? '');

        $isSafeSqliteMemory = $driver === 'sqlite' && $database === ':memory:';
        $isExplicitTestDatabase = str_ends_with($database, '_test') || str_ends_with($database, '_testing');

        if (! $isSafeSqliteMemory && ! $isExplicitTestDatabase) {
            throw new RuntimeException(
                "Refusing to refresh database [{$connectionName}:{$database}] while running tests. "
                . 'Use sqlite :memory: or a database ending with _test/_testing.'
            );
        }
    }
}
