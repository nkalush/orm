<?php

use Mockery as m;

class Monster extends juicyORM\Database\Model {}

class ORMVersionThreeTest extends PHPUnit_Framework_TestCase
{
	private $dbConfig = array('driver' => 'sqlite', 'sqlite'=>array('database'=>':memory:'));
	private $fake_monster_table = array(
			array("species"=>"Kraken","origin"=>"Norse"),
			array("species"=>"Hydra","origin"=>"Greece"),
			array("species"=>"Jormungandr","origin"=>"Norse"),
	);

	protected function tearDown()
	{
        m::close();
    }

    public function testFindQueryWithWhere()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('SELECT * FROM "monster" WHERE "species" = ?', array('Jormungandr'))->once()->andReturn(array($this->fake_monster_table[2]));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user_response = Monster::where("species","=","Jormungandr")->find();

    	$this->assertEquals(gettype($user_response), 'array');
    	$this->assertEquals(gettype($user_response[0]), 'object');
    	$this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response[0]->species, 'Jormungandr');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'SELECT * FROM "monster" WHERE "species" = ?',
    			'bindings'=>array('Jormungandr')
    		)
    	));
    }

    public function testFindQueryWithMultipleWheres()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "monster" WHERE "species" = ? AND "origin" = ?', array('Jormungandr','Norse'))->once()->andReturn(array($this->fake_monster_table[2]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user_response = Monster::where("species","=","Jormungandr")->where("origin","=","Norse")->find();

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response[0]), 'object');
        $this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response[0]->species, 'Jormungandr');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "monster" WHERE "species" = ? AND "origin" = ?',
                'bindings'=>array('Jormungandr','Norse')
            )
        ));
    }

}