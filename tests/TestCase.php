<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create the application on a database the tests are allowed to reset.
     *
     * That is the in-memory SQLite database from phpunit.xml, or, when
     * `composer test:mysql` opts in, the local MySQL database named by
     * DB_TEST_DATABASE. Anything else is refused, so a stray DB_CONNECTION or
     * DB_URL can never let RefreshDatabase wipe a database holding real data.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $config = $app['config'];

        if ($config->get('pintu.testing.mysql')) {
            $database = $config->get('pintu.testing.mysql_database');

            if (blank($database) || ! in_array($config->get('database.connections.mysql.host'), ['127.0.0.1', 'localhost', '::1'], true)) {
                throw new RuntimeException('MySQL tests need DB_TEST_DATABASE in .env and a local DB_HOST.');
            }

            $config->set('database.default', 'mysql');
            $config->set('database.connections.mysql.database', $database);
            $config->set('database.connections.mysql.url', null);

            return $app;
        }

        if ($config->get('database.default') !== 'sqlite'
            || $config->get('database.connections.sqlite.database') !== ':memory:'
            || filled($config->get('database.connections.sqlite.url'))) {
            throw new RuntimeException('Tests must use the in-memory SQLite database configured in phpunit.xml.');
        }

        return $app;
    }
}
