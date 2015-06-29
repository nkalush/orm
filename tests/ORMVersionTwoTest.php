<?php

use Mockery as m;

class Tree extends juicyORM\Database\Model
{
    public function init()
    {
        $this->has_many('leaves',array('through'=>'leaf_tree'));
    }
}

class Leaf  extends juicyORM\Database\Model {
    public function init()
    {
        $this->has_many('trees',array('through'=>'leaf_tree'));
    }
}

class ORMVersionTwoTest extends PHPUnit_Framework_TestCase
{
	private $dbConfig = array('driver' => 'sqlite', 'sqlite'=>array('database'=>':memory:'));
	private $fake_tree_table = array(
			array("tree_id"=>1,"species"=>"Oak","type"=>"Deciduous"),
			array("tree_id"=>2,"species"=>"Pine","type"=>"Conifer"),
			array("tree_id"=>3,"species"=>"Maple","type"=>"Deciduous"),
	);

	protected function tearDown()
	{
        m::close();
    }

    public function testFindQueryWithCustomSelectString()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT AVG( PERIOD_DIFF( DATE_FORMAT( CURDATE() , \'%Y%m\' ) , DATE_FORMAT( "planting_date" , \'%Y%m\' ) ) ) AS avg_lifespan FROM "tree"', array())->once()->andReturn(array(array('avg_lifespan'=>'1.5')));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new Tree($db);
        $user_response = $user
            ->select(juicyORM\Database\DB::raw('AVG( PERIOD_DIFF( DATE_FORMAT( CURDATE() , \'%Y%m\' ) , DATE_FORMAT( "planting_date" , \'%Y%m\' ) ) ) AS avg_lifespan'))
            ->find();
    }

    public function testFindQueryWithWhere()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('SELECT * FROM "tree" WHERE "species" = ?', array('Oak'))->once()->andReturn(array($this->fake_tree_table[0]));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new Tree($db);
    	$user_response = $user->where("species","=","Oak")->find();

    	$this->assertEquals(gettype($user_response), 'array');
    	$this->assertEquals(gettype($user_response[0]), 'object');
    	$this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response[0]->species, 'Oak');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'SELECT * FROM "tree" WHERE "species" = ?',
    			'bindings'=>array('Oak')
    		)
    	));
    }

    public function testFindQueryWithMultipleWheres()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" WHERE "species" = ? AND "type" = ?', array('Oak','Deciduous'))->once()->andReturn(array($this->fake_tree_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new Tree($db);
        $user_response = $user->where("species","=","Oak")->where("type","=","Deciduous")->find();

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response[0]), 'object');
        $this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response[0]->species, 'Oak');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "tree" WHERE "species" = ? AND "type" = ?',
                'bindings'=>array('Oak','Deciduous')
            )
        ));
    }

    public function testFindQueryWithJoin()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" JOIN "bird" ON "tree"."tree_id" = "bird"."tree_id" WHERE "species" = ?', array('Oak'))->once()->andReturn(array($this->fake_tree_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new Tree($db);
        $user_response = $user->join("bird","tree.tree_id","=","bird.tree_id")->where("species","=","Oak")->find();

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response[0]), 'object');
        $this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response[0]->species, 'Oak');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "tree" JOIN "bird" ON "tree"."tree_id" = "bird"."tree_id" WHERE "species" = ?',
                'bindings'=>array('Oak')
            )
        ));
    }

    public function testFindQueryWithHaving()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" HAVING "species" = ?', array('Oak'))->once()->andReturn(array($this->fake_tree_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new Tree($db);
        $user_response = $user->having("species","=","Oak")->find();

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response[0]), 'object');
        $this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response[0]->species, 'Oak');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "tree" HAVING "species" = ?',
                'bindings'=>array('Oak')
            )
        ));
    }

    public function testFindQueryWithGroupBy()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" GROUP BY "species"', array())->once()->andReturn($this->fake_tree_table);
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new Tree($db);
        $user_response = $user->groupBy("species")->find();

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response[0]), 'object');
        $this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response[0]->species, 'Oak');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "tree" GROUP BY "species"',
                'bindings'=>array()
            )
        ));
    }

    public function testFindQueryWithOrder()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" ORDER BY "tree_id" DESC', array())->once()->andReturn($this->fake_tree_table);
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new Tree($db);
        $user_response = $user->order("tree_id","DESC")->find();

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response[0]), 'object');
        $this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response[0]->species, 'Oak');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "tree" ORDER BY "tree_id" DESC',
                'bindings'=>array()
            )
        ));
    }

    public function testToArrayFunction()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" WHERE "tree_id" = ?', array('1'))->once()->andReturn($this->fake_tree_table);
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new Tree($db);
        $user_response = $user->find(1)->toArray();

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals($user_response['species'], 'Oak');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "tree" WHERE "tree_id" = ?',
                'bindings'=>array('1')
            )
        ));
    }

    public function testBasicPaginateQuery()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT COUNT(*) AS num_rows FROM "tree"', array())->once()->andReturn(array(array('num_rows'=>5)));
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" LIMIT 0, 2',array())->once()->andReturn(array($this->fake_tree_table[0],$this->fake_tree_table[1]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $tree = new Tree($db);
        $base_url = "http://example.com/";
        $user_response = $tree->paginate(2,0, $base_url);

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response['links']), 'array');
        $this->assertEquals($user_response['links'][0]['href'], $base_url);
        $this->assertEquals($user_response['links'][0]['name'], '1');
        $this->assertEquals($user_response['links'][1]['href'], $base_url.'?page=2');
        $this->assertEquals($user_response['links'][1]['name'], '2');
        $this->assertEquals(gettype($user_response['data']), 'array');
        $this->assertEquals(gettype($user_response['data'][0]), 'object');
        $this->assertEquals(get_class($user_response['data'][0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response['data'][0]->species, 'Oak');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT COUNT(*) AS num_rows FROM "tree"',
                'bindings'=>array()
            ),
            array(
                'sql'=>'SELECT * FROM "tree" LIMIT 0, 2',
                'bindings'=>array()
            )
        ));
    }

    public function testPaginateWithWhere()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT COUNT(*) AS num_rows FROM "tree" WHERE "type" = ?', array("Deciduous"))->once()->andReturn(array(array('num_rows'=>5)));
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" WHERE "type" = ? LIMIT 0, 2',array("Deciduous"))->once()->andReturn(array($this->fake_tree_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $tree = new Tree($db);
        $base_url = 'http://example.com/';
        $user_response = $tree->where('type','=','Deciduous')->paginate(2,0, $base_url);

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response['links']), 'array');
        $this->assertEquals($user_response['links'][0]['href'], $base_url);
        $this->assertEquals($user_response['links'][0]['name'], '1');
        $this->assertEquals($user_response['links'][1]['href'], $base_url.'?page=2');
        $this->assertEquals($user_response['links'][1]['name'], '2');
        $this->assertEquals(gettype($user_response['data']), 'array');
        $this->assertEquals(gettype($user_response['data'][0]), 'object');
        $this->assertEquals(get_class($user_response['data'][0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response['data'][0]->species, 'Oak');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT COUNT(*) AS num_rows FROM "tree" WHERE "type" = ?',
                'bindings'=>array("Deciduous")
            ),
            array(
                'sql'=>'SELECT * FROM "tree" WHERE "type" = ? LIMIT 0, 2',
                'bindings'=>array("Deciduous")
            )
        ));
    }

    public function testBasicPaginateQueryWithNoResults()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT COUNT(*) AS num_rows FROM "tree"', array())->once()->andReturn(array(array('num_rows'=>0)));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $tree = new Tree($db);
        $base_url = "http://example.com/";
        $user_response = $tree->paginate(2,0, $base_url);

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response['links']), 'array');
        $this->assertEquals($user_response['links'], array());
        $this->assertEquals(gettype($user_response['data']), 'array');
        $this->assertEquals($user_response['data'], array());
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT COUNT(*) AS num_rows FROM "tree"',
                'bindings'=>array()
            ),
        ));
    }

    public function testPaginateWithSelects()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT COUNT(*) AS num_rows FROM "tree" WHERE "type" = ?', array("Deciduous"))->once()->andReturn(array(array('num_rows'=>5)));
        $connection->shouldReceive('query')->with('SELECT "tree"."species" FROM "tree" WHERE "type" = ? LIMIT 0, 2',array("Deciduous"))->once()->andReturn(array($this->fake_tree_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $tree = new Tree($db);
        $base_url = 'http://example.com/';
        $user_response = $tree->select('tree.species')->where('type','=','Deciduous')->paginate(2,0, $base_url);

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response['links']), 'array');
        $this->assertEquals($user_response['links'][0]['href'], $base_url);
        $this->assertEquals($user_response['links'][0]['name'], '1');
        $this->assertEquals($user_response['links'][1]['href'], $base_url.'?page=2');
        $this->assertEquals($user_response['links'][1]['name'], '2');
        $this->assertEquals(gettype($user_response['data']), 'array');
        $this->assertEquals(gettype($user_response['data'][0]), 'object');
        $this->assertEquals(get_class($user_response['data'][0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response['data'][0]->species, 'Oak');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT COUNT(*) AS num_rows FROM "tree" WHERE "type" = ?',
                'bindings'=>array("Deciduous")
            ),
            array(
                'sql'=>'SELECT "tree"."species" FROM "tree" WHERE "type" = ? LIMIT 0, 2',
                'bindings'=>array("Deciduous")
            )
        ));
    }

    public function testPaginateFromRelationship()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" WHERE "tree_id" = ?', array(1))->once()->andReturn(array($this->fake_tree_table[0]));
        $connection->shouldReceive('query')->with('SELECT COUNT(*) AS num_rows FROM "leaf" JOIN "leaf_tree" ON "leaf_tree"."leaf_id" = "leaf"."leaf_id" WHERE "leaf_tree"."tree_id" = ? ORDER BY "leaf"."created_on" DESC', array(1))->once()->andReturn(array(array('num_rows'=>5)));
        $connection->shouldReceive('query')->with('SELECT * FROM "leaf" JOIN "leaf_tree" ON "leaf_tree"."leaf_id" = "leaf"."leaf_id" WHERE "leaf_tree"."tree_id" = ? ORDER BY "leaf"."created_on" DESC LIMIT 0, 2',array(1))->once()->andReturn(array($this->fake_tree_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $tree = new Tree($db);
        $base_url = 'http://example.com/';
        $tree = $tree->find(1);
        $user_response = $tree->leaves->order("leaf.created_on", "DESC")->paginate(2, 0, $base_url);

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response['links']), 'array');
        $this->assertEquals($user_response['links'][0]['href'], $base_url);
        $this->assertEquals($user_response['links'][0]['name'], '1');
        $this->assertEquals($user_response['links'][1]['href'], $base_url.'?page=2');
        $this->assertEquals($user_response['links'][1]['name'], '2');
        $this->assertEquals(gettype($user_response['data']), 'array');
        $this->assertEquals(gettype($user_response['data'][0]), 'object');
        $this->assertEquals(get_class($user_response['data'][0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "tree" WHERE "tree_id" = ?',
                'bindings'=>array(1)
            ),
            array(
                'sql'=>'SELECT COUNT(*) AS num_rows FROM "leaf" JOIN "leaf_tree" ON "leaf_tree"."leaf_id" = "leaf"."leaf_id" WHERE "leaf_tree"."tree_id" = ? ORDER BY "leaf"."created_on" DESC',
                'bindings'=>array(1)
            ),
            array(
                'sql'=>'SELECT * FROM "leaf" JOIN "leaf_tree" ON "leaf_tree"."leaf_id" = "leaf"."leaf_id" WHERE "leaf_tree"."tree_id" = ? ORDER BY "leaf"."created_on" DESC LIMIT 0, 2',
                'bindings'=>array(1)
            )
        ));
    }

    public function testIsConnected()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" WHERE "tree_id" = ?',array(1))->once()->andReturn(array($this->fake_tree_table[0]));
        $connection->shouldReceive('query')->with('SELECT * FROM "leaf" JOIN "leaf_tree" ON "leaf_tree"."leaf_id" = "leaf"."leaf_id" WHERE "leaf_tree"."tree_id" = ? AND "leaf"."leaf_id" = ?', array(1,2))->once()->andReturn(array($this->fake_tree_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $tree = new Tree($db);
        $user_response = $tree->find(1)->leaves->is_connected(2);

        $this->assertEquals(gettype($user_response), 'boolean');
        $this->assertEquals($user_response, true);
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "tree" WHERE "tree_id" = ?',
                'bindings'=>array(1)
            ),
            array(
                'sql'=>'SELECT * FROM "leaf" JOIN "leaf_tree" ON "leaf_tree"."leaf_id" = "leaf"."leaf_id" WHERE "leaf_tree"."tree_id" = ? AND "leaf"."leaf_id" = ?',
                'bindings'=>array(1,2)
            )
        ));
    }

    public function testIsConnectedFailure()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" WHERE "tree_id" = ?',array(1))->once()->andReturn(array($this->fake_tree_table[0]));
        $connection->shouldReceive('query')->with('SELECT * FROM "leaf" JOIN "leaf_tree" ON "leaf_tree"."leaf_id" = "leaf"."leaf_id" WHERE "leaf_tree"."tree_id" = ? AND "leaf"."leaf_id" = ?', array(1,2))->once()->andReturn(null);
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $tree = new Tree($db);
        $user_response = $tree->find(1)->leaves->is_connected(2);

        $this->assertEquals(gettype($user_response), 'boolean');
        $this->assertEquals($user_response, false);
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "tree" WHERE "tree_id" = ?',
                'bindings'=>array(1)
            ),
            array(
                'sql'=>'SELECT * FROM "leaf" JOIN "leaf_tree" ON "leaf_tree"."leaf_id" = "leaf"."leaf_id" WHERE "leaf_tree"."tree_id" = ? AND "leaf"."leaf_id" = ?',
                'bindings'=>array(1,2)
            )
        ));
    }

    public function testAttach()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" WHERE "tree_id" = ?',array(1))->once()->andReturn(array($this->fake_tree_table[0]));
        $connection->shouldReceive('query')->with('INSERT INTO "leaf_tree" ("tree_id", "leaf_id") VALUES (?, ?)', array(1,2), false)->once()->andReturn(true);
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $tree = new Tree($db);
        $user_response = $tree->find(1)->leaves->attach(2);

        $this->assertEquals(gettype($user_response), 'boolean');
        $this->assertEquals($user_response, true);
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "tree" WHERE "tree_id" = ?',
                'bindings'=>array(1)
            ),
            array(
                'sql'=>'INSERT INTO "leaf_tree" ("tree_id", "leaf_id") VALUES (?, ?)',
                'bindings'=>array(1,2)
            )
        ));
    }

    public function testDetachOne()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" WHERE "tree_id" = ?',array(1))->once()->andReturn(array($this->fake_tree_table[0]));
        $connection->shouldReceive('query')->with('DELETE FROM "leaf_tree" WHERE "tree_id" = ? AND "leaf_id" = ?', array(1,2), false)->once()->andReturn(true);
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $tree = new Tree($db);
        $user_response = $tree->find(1)->leaves->detach(2);

        $this->assertEquals(gettype($user_response), 'boolean');
        $this->assertEquals($user_response, true);
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "tree" WHERE "tree_id" = ?',
                'bindings'=>array(1)
            ),
            array(
                'sql'=>'DELETE FROM "leaf_tree" WHERE "tree_id" = ? AND "leaf_id" = ?',
                'bindings'=>array(1,2)
            )
        ));
    }

    public function testDetachAll()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "tree" WHERE "tree_id" = ?',array(1))->once()->andReturn(array($this->fake_tree_table[0]));
        $connection->shouldReceive('query')->with('DELETE FROM "leaf_tree" WHERE "tree_id" = ?', array(1), false)->once()->andReturn(true);
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $tree = new Tree($db);
        $user_response = $tree->find(1)->leaves->detach_all();

        $this->assertEquals(gettype($user_response), 'boolean');
        $this->assertEquals($user_response, true);
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "tree" WHERE "tree_id" = ?',
                'bindings'=>array(1)
            ),
            array(
                'sql'=>'DELETE FROM "leaf_tree" WHERE "tree_id" = ?',
                'bindings'=>array(1)
            )
        ));
    }
}