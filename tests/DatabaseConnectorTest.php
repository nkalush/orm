<?php

use Mockery as m;

class DatabaseConnectorTest extends PHPUnit_Framework_TestCase
{
    protected function tearDown(){
        m::close();
    }
    
    /**
     * @dataProvider mySqlConnectProvider
     */
    public function testMySqlConnect($dsn, $config)
    {
        $connector = $this->getMock('juicyORM\Database\Connectors\MySqlConnector', array('createConnection', 'getOptions'));
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(array('options')));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(array('options')))->will($this->returnValue($connection));
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\' collate \'utf8_unicode_ci\'')->andReturn($connection);
        $connection->shouldReceive('execute')->once();
        $connection->shouldReceive('exec')->zeroOrMoreTimes();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function mySqlConnectProvider()
    {
        return array(
            array('mysql:host=foo;dbname=bar', array('host' => 'foo', 'database' => 'bar', 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8')),
            array('mysql:host=foo;port=111;dbname=bar', array('host' => 'foo', 'database' => 'bar', 'port' => 111, 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8')),
            array('mysql:unix_socket=baz;dbname=bar', array('host' => 'foo', 'database' => 'bar', 'port' => 111, 'unix_socket' => 'baz', 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8')),
        );
    }

    public function testSQLiteMemoryDatabasesMayBeConnectedTo()
    {
        $dsn = 'sqlite::memory:';
        $config = array('database' => ':memory:');
        $connector = $this->getMock('juicyORM\Database\Connectors\SQLiteConnector', array('createConnection', 'getOptions'));
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(array('options')));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(array('options')))->will($this->returnValue($connection));
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testSQLiteFileDatabasesMayBeConnectedTo()
    {
        $dsn = 'sqlite:'.__DIR__;
        $config = array('database' => __DIR__);
        $connector = $this->getMock('juicyORM\Database\Connectors\SQLiteConnector', array('createConnection', 'getOptions'));
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(array('options')));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(array('options')))->will($this->returnValue($connection));
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }
}