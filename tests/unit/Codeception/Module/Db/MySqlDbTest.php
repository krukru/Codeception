<?php

require_once \Codeception\Configuration::testsDir().'unit/Codeception/Module/Db/TestsForDb.php';

/**
 * @group appveyor
 * @group db
 */
class MySqlDbTest extends TestsForDb
{
    public function getPopulator()
    {
        if (getenv('APPVEYOR')) {
            $this->markTestSkipped('Disabled on Appveyor');
        }
        $config = $this->getConfig();
        $password = $config['password'] ? '-p'.$config['password'] : '';
        return "mysql -u \$user $password \$dbname < {$config['dump']}";
    }

    public function getConfig()
    {
        return [
            'dsn' => 'mysql:host=localhost;dbname=codeception_test',
            'user' => 'root',
            'password' => getenv('APPVEYOR') ? 'Password12!' : '',
            'dump' => 'tests/data/dumps/mysql.sql',
            'reconnect' => true,
            'cleanup' => true,
            'populate' => true
        ];
    }

    /**
     * Overriden, Using MYSQL CONNECTION_ID to get current connection
     */
    public function testConnectionIsResetOnEveryTestWhenReconnectIsTrue()
    {
        $testCase1 = \Codeception\Util\Stub::makeEmpty('\Codeception\TestInterface');
        $testCase2 = \Codeception\Util\Stub::makeEmpty('\Codeception\TestInterface');
        $testCase3 = \Codeception\Util\Stub::makeEmpty('\Codeception\TestInterface');


        $this->module->_setConfig(['reconnect' => false]);
        $this->module->_beforeSuite();

        // Simulate a test that runs
        $this->module->_before($testCase1);
        $connection1 = $this->module->dbh->query('SELECT CONNECTION_ID()')->fetch(PDO::FETCH_COLUMN);
        $this->module->_after($testCase1);

        // Simulate a second test that runs
        $this->module->_before($testCase2);
        $connection2 = $this->module->dbh->query('SELECT CONNECTION_ID()')->fetch(PDO::FETCH_COLUMN);
        $this->module->_after($testCase2);
        $this->module->_afterSuite();

        $this->module->_setConfig(['reconnect' => true]);
        $this->module->_before($testCase3);
        $connection3 = $this->module->dbh->query('SELECT CONNECTION_ID()')->fetch(PDO::FETCH_COLUMN);
        $this->module->_after($testCase3);

        $this->assertEquals($connection1, $connection2);
        $this->assertNotEquals($connection3, $connection2);
    }

    public function testInitialQueriesAreExecuted()
    {
        $dbName = 'test_db';
        $config = $this->module->_getConfig();
        $config['initial_queries'] = [
            'CREATE DATABASE IF NOT EXISTS ' . $dbName . ';',
            'USE ' . $dbName . ';',
        ];
        $this->module->_reconfigure($config);
        $this->module->_before(\Codeception\Util\Stub::makeEmpty('\Codeception\TestInterface'));
        $usedDatabaseName = $this->module->dbh->query('SELECT DATABASE();')->fetch(PDO::FETCH_COLUMN);

        $this->assertEquals($dbName, $usedDatabaseName);
    }

    public function testGrabColumnFromDatabase()
    {
        $emails = $this->module->grabColumnFromDatabase('users', 'email');
        $this->assertEquals(
            [
                'davert@mail.ua',
                'nick@mail.ua',
                'miles@davis.com',
                'charlie@parker.com',
            ],
            $emails);
    }

}
