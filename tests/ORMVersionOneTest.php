<?php

use Mockery as m;

class User extends juicyORM\Database\Model {

	public function init()
	{
		$this->has_many('hats');
        $this->belongs_to('hat');
        $this->has_many('shirts',array('through'=>'user_shirt'));
        $this->has_many('kilts',array('through'=>'user_pants', 'class_name'=>'pants'));
	}

    public function get_md5email($row)
    {
        return md5($row->email);
    }
}
class Hat extends juicyORM\Database\Model {}
class Shirt extends juicyORM\Database\Model {}
class Pants extends juicyORM\Database\Model {}
class Person extends juicyORM\Database\Model {
	protected $table_name = "Peoplez";
	protected $primary_key = "Peoplez_id";
}
class Admin extends juicyORM\Database\Model {

    public function init()
    {
        $this->before_save('changeName');
    }

    public function changeName($row=array(),$data=array())
    {
        $data['username'] = 'Steve'.(!empty($row)?$row->admin_id:'');
        return $data;
    }
}
class ORMVersionOneTest extends PHPUnit_Framework_TestCase
{
	private $dbConfig = array('driver' => 'sqlite', 'sqlite'=>array('database'=>':memory:'));
	private $fake_user_table = array(
			array("user_id"=>1, "hat_id"=>1, "username"=>"Nick", "email"=>"nick@example.com"),
			array("user_id"=>2, "hat_id"=>2, "username"=>"James", "email"=>"james@example.com"),
			array("user_id"=>3, "hat_id"=>3, "username"=>"Derek", "email"=>"derek@example.com"),
	);
	private $fake_hat_table = array(
			array("hat_id"=>1, "user_id"=>1, "color"=>"Black", "type"=>"Fedora"),
			array("hat_id"=>2, "user_id"=>2, "color"=>"Brown", "type"=>"Stetson"),
			array("hat_id"=>3, "user_id"=>1, "color"=>"Black", "type"=>"Tophat"),
	);

	protected function tearDown()
	{
        m::close();
    }

    public function testGetsCorrectTableNameBasic()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);

    	$this->assertEquals($user->table_name(), "user");
    }

    public function testGetsCorrectTableNameWithPrefix()
    {
    	$newConfig = $this->dbConfig;
    	$newConfig['sqlite']['prefix'] = 'salsa_';

    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$db = juicyORM\Database\DB::Instance($newConfig, $connection, true);

    	$user = new User($db);

    	$this->assertEquals($user->table_name(), "salsa_user");
    }

    public function testGetsCorrectTableNameWhenSet()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$person = new Person($db);

    	$this->assertEquals($person->table_name(), "Peoplez");
    }

    public function testGetsCorrectPrimaryKeyBasic()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);

    	$this->assertEquals($user->primary_key(), "user_id");
    }

    public function testGetsCorrectPrimaryKeyWhenSet()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new Person($db);

    	$this->assertEquals($user->primary_key(), "Peoplez_id");
    }

    public function testBasicFindQuery()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user"', array())->once()->andReturn($this->fake_user_table);
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find();

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response[0]), 'object');
        $this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response[0]->username, 'Nick');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "user"',
                'bindings'=>array(),
            )
        ));
    }

    public function testBasicFirstQuery()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('SELECT * FROM "user" LIMIT 0, 1', array())->once()->andReturn(array($this->fake_user_table[0]));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->first();

    	$this->assertEquals(gettype($user_response), 'object');
    	$this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response->username, 'Nick');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'SELECT * FROM "user" LIMIT 0, 1',
    			'bindings'=>array(),
    		)
    	));
    }

    public function testFindQueryWithID()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array(1))->once()->andReturn(array($this->fake_user_table[0]));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->find(1);

    	$this->assertEquals(gettype($user_response), 'object');
    	$this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response->username, 'Nick');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
    			'bindings'=>array(1)
    		)
    	));
    }

    public function testFindQueryWithCustomSelect()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT "user"."first_name", "user"."last_name" FROM "user"', array())->once()->andReturn($this->fake_user_table);
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(array("select"=>array("user.first_name","user.last_name")));

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response[0]), 'object');
        $this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response[0]->username, 'Nick');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT "user"."first_name", "user"."last_name" FROM "user"',
                'bindings'=>array()
            )
        ));
    }

    public function testFindQueryWithCustomSelectDoesntEscapeAsterisk()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('SELECT "user".*, "user"."last_name" FROM "user"', array())->once()->andReturn($this->fake_user_table);
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->find(array("select"=>array("user.*","user.last_name")));

    	$this->assertEquals(gettype($user_response), 'array');
    	$this->assertEquals(gettype($user_response[0]), 'object');
    	$this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response[0]->username, 'Nick');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'SELECT "user".*, "user"."last_name" FROM "user"',
    			'bindings'=>array()
    		)
    	));
    }

    public function testFindQueryWithWhere()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "first_name" = ?', array('James'))->once()->andReturn(array($this->fake_user_table[1]));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->find(array("where"=>array("first_name"=>"James")));

    	$this->assertEquals(gettype($user_response), 'array');
    	$this->assertEquals(gettype($user_response[0]), 'object');
    	$this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response[0]->username, 'James');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'SELECT * FROM "user" WHERE "first_name" = ?',
    			'bindings'=>array('James')
    		)
    	));
    }

    public function testFindQueryWithWhereIn()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "first_name" IN (?, ?)', array('James','Derek'))->once()->andReturn(array($this->fake_user_table[1]));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->find(array("where_in"=>array("first_name"=>array("James","Derek"))));

    	$this->assertEquals(gettype($user_response), 'array');
    	$this->assertEquals(gettype($user_response[0]), 'object');
    	$this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response[0]->username, 'James');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'SELECT * FROM "user" WHERE "first_name" IN (?, ?)',
    			'bindings'=>array('James','Derek')
    		)
    	));
    }

    public function testFindQueryWithHaving()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('SELECT * FROM "user" HAVING "total_cats" > ?', array('10'))->once()->andReturn(array($this->fake_user_table[1]));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->find(array("having"=>array("total_cats > 10")));

    	$this->assertEquals(gettype($user_response), 'array');
    	$this->assertEquals(gettype($user_response[0]), 'object');
    	$this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response[0]->username, 'James');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'SELECT * FROM "user" HAVING "total_cats" > ?',
    			'bindings'=>array('10')
    		)
    	));
    }

    public function testFindQueryWithOrder()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('SELECT * FROM "user" ORDER BY "last_name" ASC', array())->once()->andReturn(array($this->fake_user_table[1]));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->find(array("order"=>array("last_name ASC")));

    	$this->assertEquals(gettype($user_response), 'array');
    	$this->assertEquals(gettype($user_response[0]), 'object');
    	$this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response[0]->username, 'James');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'SELECT * FROM "user" ORDER BY "last_name" ASC',
    			'bindings'=>array()
    		)
    	));
    }

    public function testFindQueryWithLimit()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('SELECT * FROM "user" LIMIT 0, 1', array())->once()->andReturn(array($this->fake_user_table[1]));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->find(array("limit"=>1));

    	$this->assertEquals(gettype($user_response), 'array');
    	$this->assertEquals(gettype($user_response[0]), 'object');
    	$this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response[0]->username, 'James');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'SELECT * FROM "user" LIMIT 0, 1',
    			'bindings'=>array()
    		)
    	));
    }

    public function testFindQueryWithLimitAndOffset()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" LIMIT 5, 1', array())->once()->andReturn(array($this->fake_user_table[1]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(array("limit"=>1, "offset"=>5));

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response[0]), 'object');
        $this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response[0]->username, 'James');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "user" LIMIT 5, 1',
                'bindings'=>array()
            )
        ));
    }

    public function testCountQuery()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('SELECT COUNT(*) AS num_rows FROM "user"', array())->once()->andReturn(array(array('num_rows'=>5)));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->count();

    	$this->assertEquals(gettype($user_response), 'integer');
    	$this->assertEquals($user_response, 5);
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'SELECT COUNT(*) AS num_rows FROM "user"',
    			'bindings'=>array()
    		)
    	));
    }

    public function testUpdateQuery()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('UPDATE "user" SET "username" = ?, "email" = ? WHERE "user_id" = ?', array('Derek','derek@example.com','5'), false)->once();
    	$connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('5'))->once()->andReturn(array($this->fake_user_table[2]));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->update(5, array("username"=>"Derek","email"=>"derek@example.com"));

    	$this->assertEquals(gettype($user_response), 'object');
    	$this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response->username, 'Derek');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'UPDATE "user" SET "username" = ?, "email" = ? WHERE "user_id" = ?',
    			'bindings'=>array('Derek','derek@example.com','5')
    		),
    		array(
    			'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
    			'bindings'=>array('5')
    		)
    	));
    }

    public function testInsertQuery()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('INSERT INTO "user" ("username", "email") VALUES (?, ?)', array('Derek','derek@example.com'), false)->once()->andReturn(true);
    	$connection->shouldReceive('lastInsertId')->with('user_id')->once()->andReturn('5');
    	$connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('5'))->once()->andReturn(array($this->fake_user_table[2]));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->create(array("username"=>"Derek","email"=>"derek@example.com"));

    	$this->assertEquals(gettype($user_response), 'object');
    	$this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response->username, 'Derek');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'INSERT INTO "user" ("username", "email") VALUES (?, ?)',
    			'bindings'=>array('Derek','derek@example.com')
    		),
    		array(
    			'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
    			'bindings'=>array('5')
    		)
    	));
    }

    public function testBeforeSaveOnInsert()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('INSERT INTO "admin" ("username", "email") VALUES (?, ?)', array('Steve','derek@example.com'), false)->once()->andReturn(true);
        $connection->shouldReceive('lastInsertId')->with('admin_id')->once()->andReturn('5');
        $connection->shouldReceive('query')->with('SELECT * FROM "admin" WHERE "admin_id" = ?', array('5'))->once()->andReturn(array(array("user_id"=>3, "username"=>"Steve", "email"=>"derek@example.com")));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $admin = new Admin($db);
        $user_response = $admin->create(array("username"=>"Derek","email"=>"derek@example.com"));

        $this->assertEquals(gettype($user_response), 'object');
        $this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response->username, 'Steve');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'INSERT INTO "admin" ("username", "email") VALUES (?, ?)',
                'bindings'=>array('Steve','derek@example.com')
            ),
            array(
                'sql'=>'SELECT * FROM "admin" WHERE "admin_id" = ?',
                'bindings'=>array('5')
            )
        ));
    }

    public function testBeforeSaveOnUpdate()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "admin" WHERE "admin_id" = ? LIMIT 0, 1', array('5'))->once()->andReturn(array(array("admin_id"=>5, "username"=>"Steve", "email"=>"derek@example.com")));
        $connection->shouldReceive('query')->with('UPDATE "admin" SET "username" = ?, "email" = ? WHERE "admin_id" = ?', array('Steve5','derek@example.com', 5), false)->once()->andReturn(true);
        $connection->shouldReceive('query')->with('SELECT * FROM "admin" WHERE "admin_id" = ?', array('5'))->once()->andReturn(array(array("admin_id"=>5, "username"=>"Steve5", "email"=>"derek@example.com")));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $admin = new Admin($db);
        $user_response = $admin->update(5, array("username"=>"Derek","email"=>"derek@example.com"));

        $this->assertEquals(gettype($user_response), 'object');
        $this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response->username, 'Steve5');
    }

    public function testBeforeSaveOnUpdateFromModelRow()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "admin" WHERE "admin_id" = ? LIMIT 0, 1', array('5'))->once()->andReturn(array(array("admin_id"=>5, "username"=>"Steve", "email"=>"derek@example.com")));
        $connection->shouldReceive('query')->with('UPDATE "admin" SET "username" = ?, "email" = ? WHERE "admin_id" = ?', array('Steve5','derek@example.com', 5), false)->once()->andReturn(true);
        $connection->shouldReceive('query')->with('SELECT * FROM "admin" WHERE "admin_id" = ?', array('5'))->times(2)->andReturn(array(array("admin_id"=>5, "username"=>"Steve5", "email"=>"derek@example.com")));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $admin = new Admin($db);
        $user_response = $admin->find(5)->update(array("username"=>"Derek","email"=>"derek@example.com"));

        $this->assertEquals(gettype($user_response), 'object');
        $this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response->username, 'Steve5');
    }

    public function testDeleteByIDQuery()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('DELETE FROM "user" WHERE "user_id" = ?', array('5'), false)->once()->andReturn(true);
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->destroy(5);

    	$this->assertEquals(gettype($user_response), 'boolean');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'DELETE FROM "user" WHERE "user_id" = ?',
    			'bindings'=>array('5')
    		),
    	));
    }

    public function testDeleteWhereQuery()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('DELETE FROM "user" WHERE "user_id" = ?', array('5'), false)->once()->andReturn(true);
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->where('user_id','=',5)->destroy();

    	$this->assertEquals(gettype($user_response), 'boolean');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'DELETE FROM "user" WHERE "user_id" = ?',
    			'bindings'=>array('5')
    		),
    	));
    }

    public function testAssocHasMany()
    {
    	$connection = m::mock('juicyORM\Database\DbConnection');
    	$connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_user_table[0]));
    	$connection->shouldReceive('query')->with('SELECT * FROM "hat" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_hat_table[0],$this->fake_hat_table[2]));
    	$db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

    	$user = new User($db);
    	$user_response = $user->find(1)->hats->find();

    	$this->assertEquals(gettype($user_response), 'array');
    	$this->assertEquals(gettype($user_response[0]), 'object');
    	$this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
    	$this->assertEquals($user_response[0]->type, 'Fedora');
    	$this->assertEquals($db->runtime_info(), array(
    		array(
    			'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
    			'bindings'=>array('1')
    		),
    		array(
    			'sql'=>'SELECT * FROM "hat" WHERE "user_id" = ?',
    			'bindings'=>array('1')
    		),
    	));
    }

    public function testAssocHasManyWithWhere()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_user_table[0]));
        $connection->shouldReceive('query')->with('SELECT * FROM "hat" WHERE "user_id" = ? AND "color" = ?', array('1','Black'))->once()->andReturn(array($this->fake_hat_table[0],$this->fake_hat_table[2]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(1)->hats->where('color','=','Black')->find();

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response[0]), 'object');
        $this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response[0]->type, 'Fedora');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
            array(
                'sql'=>'SELECT * FROM "hat" WHERE "user_id" = ? AND "color" = ?',
                'bindings'=>array('1','Black')
            ),
        ));
    }

    public function testAssocBelongsTo()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_user_table[0]));
        $connection->shouldReceive('query')->with('SELECT * FROM "hat" WHERE "hat_id" = ?', array('1'))->once()->andReturn(array($this->fake_hat_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(1)->hat;

        $this->assertEquals(gettype($user_response), 'object');
        $this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response->type, 'Fedora');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
            array(
                'sql'=>'SELECT * FROM "hat" WHERE "hat_id" = ?',
                'bindings'=>array('1')
            ),
        ));
    }

    public function testAssocHasManyThrough()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_user_table[0]));
        //Obviously, this wouldn't really return hats, but whatevs
        $connection->shouldReceive('query')->with('SELECT * FROM "shirt" JOIN "user_shirt" ON "user_shirt"."shirt_id" = "shirt"."shirt_id" WHERE "user_shirt"."user_id" = ?', array('1'))->once()->andReturn(array($this->fake_hat_table[0],$this->fake_hat_table[2]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(1)->shirts->find();

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response[0]), 'object');
        $this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response[0]->type, 'Fedora');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
            array(
                'sql'=>'SELECT * FROM "shirt" JOIN "user_shirt" ON "user_shirt"."shirt_id" = "shirt"."shirt_id" WHERE "user_shirt"."user_id" = ?',
                'bindings'=>array('1')
            ),
        ));
    }

    public function testAssocHasManyThroughWithCustomClass()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_user_table[0]));
        //Obviously, this wouldn't really return hats, but whatevs
        $connection->shouldReceive('query')->with('SELECT * FROM "pants" JOIN "user_pants" ON "user_pants"."pants_id" = "pants"."pants_id" WHERE "user_pants"."user_id" = ?', array('1'))->once()->andReturn(array($this->fake_hat_table[0],$this->fake_hat_table[2]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(1)->kilts->find();

        $this->assertEquals(gettype($user_response), 'array');
        $this->assertEquals(gettype($user_response[0]), 'object');
        $this->assertEquals(get_class($user_response[0]), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response[0]->type, 'Fedora');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
            array(
                'sql'=>'SELECT * FROM "pants" JOIN "user_pants" ON "user_pants"."pants_id" = "pants"."pants_id" WHERE "user_pants"."user_id" = ?',
                'bindings'=>array('1')
            ),
        ));
    }

    public function testAssocHasManyThroughThenSelectOne()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_user_table[0]));
        //Obviously, this wouldn't really return hats, but whatevs
        $connection->shouldReceive('query')->with('SELECT * FROM "shirt" JOIN "user_shirt" ON "user_shirt"."shirt_id" = "shirt"."shirt_id" WHERE "user_shirt"."user_id" = ? AND "shirt"."shirt_id" = ?', array('1','2'))->once()->andReturn(array($this->fake_hat_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(1)->shirts->find(2);

        $this->assertEquals(gettype($user_response), 'object');
        $this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response->type, 'Fedora');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
            array(
                'sql'=>'SELECT * FROM "shirt" JOIN "user_shirt" ON "user_shirt"."shirt_id" = "shirt"."shirt_id" WHERE "user_shirt"."user_id" = ? AND "shirt"."shirt_id" = ?',
                'bindings'=>array('1','2')
            ),
        ));
    }

    public function testAssocCacheing()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_user_table[0]));
        $connection->shouldReceive('query')->with('SELECT * FROM "hat" WHERE "hat_id" = ?', array('1'))->once()->andReturn(array($this->fake_hat_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(1);
        $type = $user_response->hat->type;
        $color = $user_response->hat->color;

        $this->assertEquals(gettype($user_response), 'object');
        $this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
        $this->assertEquals($type, 'Fedora');
        $this->assertEquals($color, 'Black');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
            array(
                'sql'=>'SELECT * FROM "hat" WHERE "hat_id" = ?',
                'bindings'=>array('1')
            ),
        ));
    }

    public function testAssocCacheingDoesntBreakSuccessiveCalls()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_user_table[0]));
        $connection->shouldReceive('query')->with('SELECT * FROM "hat" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_hat_table[0]));
        $connection->shouldReceive('query')->with('SELECT * FROM "hat" WHERE "user_id" = ? AND "color" = ?', array('1','Black'))->once()->andReturn(array($this->fake_hat_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(1);
        $user_response->hats->find();
        $hat = $user_response->hats->where('color','=','Black')->find();

        $this->assertEquals(gettype($user_response), 'object');
        $this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
        $this->assertEquals($hat[0]->type, 'Fedora');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
            array(
                'sql'=>'SELECT * FROM "hat" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
            array(
                'sql'=>'SELECT * FROM "hat" WHERE "user_id" = ? AND "color" = ?',
                'bindings'=>array('1','Black')
            ),
        ));
    }

    public function testHasManyThroughAssocCacheingDoesntBreakSuccessiveCalls()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_user_table[0]));
        $connection->shouldReceive('query')->with('SELECT * FROM "shirt" JOIN "user_shirt" ON "user_shirt"."shirt_id" = "shirt"."shirt_id" WHERE "user_shirt"."user_id" = ?', array('1'))->once()->andReturn(array($this->fake_hat_table[0]));
        $connection->shouldReceive('query')->with('SELECT * FROM "shirt" JOIN "user_shirt" ON "user_shirt"."shirt_id" = "shirt"."shirt_id" WHERE "user_shirt"."user_id" = ? AND "color" = ?', array('1','Black'))->once()->andReturn(array($this->fake_hat_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(1);
        $user_response->shirts->find();
        $hat = $user_response->shirts->where('color','=','Black')->find();

        $this->assertEquals(gettype($user_response), 'object');
        $this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
        $this->assertEquals($hat[0]->type, 'Fedora');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
            array(
                'sql'=>'SELECT * FROM "shirt" JOIN "user_shirt" ON "user_shirt"."shirt_id" = "shirt"."shirt_id" WHERE "user_shirt"."user_id" = ?',
                'bindings'=>array('1')
            ),
            array(
                'sql'=>'SELECT * FROM "shirt" JOIN "user_shirt" ON "user_shirt"."shirt_id" = "shirt"."shirt_id" WHERE "user_shirt"."user_id" = ? AND "color" = ?',
                'bindings'=>array('1','Black')
            ),
        ));
    }

    public function testCustomGetFunctionAsVariable()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_user_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(1)->md5email;

        $this->assertEquals($user_response, md5($this->fake_user_table[0]['email']));
    }

    public function testCustomGetFunctionAsFunction()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_user_table[0]));
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(1)->md5email();

        $this->assertEquals($user_response, md5($this->fake_user_table[0]['email']));
    }

    public function testModelRowAddData()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);
        $model = new User($db);
        $row = new juicyORM\Database\ModelRow($model, $this->fake_user_table[0]);
        $row->add_data("test","result");

        $this->assertEquals($row->test, "result");
    }

    public function testUpdateViaModelRow()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->twice()->andReturn(array($this->fake_user_table[0]));
        $connection->shouldReceive('query')->with('UPDATE "user" SET "username" = ?, "email" = ? WHERE "user_id" = ?', array('Derek','derek@example.com', '1'), false)->once();
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(1)->update(array("username"=>"Derek","email"=>"derek@example.com"));

        $this->assertEquals(gettype($user_response), 'object');
        $this->assertEquals(get_class($user_response), 'juicyORM\Database\ModelRow');
        $this->assertEquals($user_response->username, 'Nick');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
            array(
                'sql'=>'UPDATE "user" SET "username" = ?, "email" = ? WHERE "user_id" = ?',
                'bindings'=>array('Derek','derek@example.com','1')
            ),
            array(
                'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
        ));
    }

    public function testDestroyViaModelRow()
    {
        $connection = m::mock('juicyORM\Database\DbConnection');
        $connection->shouldReceive('query')->with('SELECT * FROM "user" WHERE "user_id" = ?', array('1'))->once()->andReturn(array($this->fake_user_table[0]));
        $connection->shouldReceive('query')->with('DELETE FROM "user" WHERE "user_id" = ?', array('1'), false)->once()->andReturn(true);
        $db = juicyORM\Database\DB::Instance($this->dbConfig, $connection, true);

        $user = new User($db);
        $user_response = $user->find(1)->destroy();

        $this->assertEquals(gettype($user_response), 'NULL');
        $this->assertEquals($db->runtime_info(), array(
            array(
                'sql'=>'SELECT * FROM "user" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
            array(
                'sql'=>'DELETE FROM "user" WHERE "user_id" = ?',
                'bindings'=>array('1')
            ),
        ));
    }
}