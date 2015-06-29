<?php

class DatabaseQueryTest extends PHPUnit_Framework_TestCase
{

    public function testBasicSelectQuery()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users');

        $this->assertEquals('SELECT * FROM "users"', $db->toSql());
    }

    public function testMySqlSanitizeUsesBackticks()
    {
        $db = new juicyORM\Database\Drivers\MySqlDriver;
        $db->select('*')->from('users');

        $this->assertEquals('SELECT * FROM `users`', $db->toSql());
    }

    public function testBasicTableWrappingProtectsQuotationMarks()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('some"table');
        $this->assertEquals('SELECT * FROM "some""table"', $db->toSql());
    }

    public function testMySqlWrappingProtectsQuotationMarks()
    {
        $db = new juicyORM\Database\Drivers\MySqlDriver;
        $db->select('*')->from('some`table');
        $this->assertEquals('SELECT * FROM `some``table`', $db->toSql());
    }

    public function testBasicSelectWithPrefix()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->setTablePrefix('prefix_')->select('*')->from('users');

        $this->assertEquals('SELECT * FROM "prefix_users"', $db->toSql());
    }

    public function testBasicSelectDistinct()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->distinct()->select('*')->from('users');

        $this->assertEquals('SELECT DISTINCT * FROM "users"', $db->toSql());
    }

    public function testSelectArrayQuery()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select(array('users.first_name','users.last_name'))->from('users')->where("user_id","=","1");

        $this->assertEquals('SELECT "users"."first_name", "users"."last_name" FROM "users" WHERE "user_id" = ?', $db->toSql());
        $this->assertEquals(array("1"), $db->getBindings());
    }

    public function testSanitizesSelectString()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('users.first_name, users.last_name')->from('users')->where("user_id","=","1");

        $this->assertEquals('SELECT "users"."first_name", "users"."last_name" FROM "users" WHERE "user_id" = ?', $db->toSql());
        $this->assertEquals(array("1"), $db->getBindings());
    }

    public function testBasicAlias()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('foo AS bar')->from('users');
        $this->assertEquals('SELECT "foo" AS bar FROM "users"', $db->toSql());
    }

    public function testBasicTableWrapping()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('public.users');
        $this->assertEquals('SELECT * FROM "public"."users"', $db->toSql());
    }

    public function testBasicWheres()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->where("user_id","=","1");

        $this->assertEquals('SELECT * FROM "users" WHERE "user_id" = ?', $db->toSql());
        $this->assertEquals(array("1"), $db->getBindings());
    }

    public function testMultipleWheresQuery()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->where("first_name","=","John")->where("last_name","=","Smith");

        $this->assertEquals('SELECT * FROM "users" WHERE "first_name" = ? AND "last_name" = ?', $db->toSql());
        $this->assertEquals(array("John","Smith"), $db->getBindings());
    }

    public function testWhereInQuery()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $names = array("Derek","James","Nick");
        $db->select('*')->from('users')->where("first_name","IN",$names);

        $this->assertEquals('SELECT * FROM "users" WHERE "first_name" IN (?, ?, ?)', $db->toSql());
        $this->assertEquals($names, $db->getBindings());
    }

    public function testProtectedFunctionsDontUsePDOInWhere()
    {
        $db = new juicyORM\Database\Drivers\MySqlDriver;
        $db->select('*')->from('users')->where("created_on",">=",'NOW()');

        $this->assertEquals('SELECT * FROM `users` WHERE `created_on` >= NOW()', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testProtectedFunctionsDontUsePDOInSet()
    {
        $db = new juicyORM\Database\Drivers\MySqlDriver;
        $db->from('users')->set("created_on",'NOW()')->update();

        $this->assertEquals('UPDATE `users` SET `created_on` = NOW()', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testProtectedFunctionsDontUsePDOInValues()
    {
        $db = new juicyORM\Database\Drivers\MySqlDriver;
        $db->from('users')->set("created_on",'NOW()')->insert();

        $this->assertEquals('INSERT INTO `users` (`created_on`) VALUES (NOW())', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testOrWhereQuery()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->where("first_name","=","Derek")->or_where("first_name","=","James");

        $this->assertEquals('SELECT * FROM "users" WHERE "first_name" = ? OR "first_name" = ?', $db->toSql());
        $this->assertEquals(array("Derek","James"), $db->getBindings());
    }

    public function testNestedWhereQuery()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->where(function($query)
            {
                $query->where("first_name","=","Derek");
                $query->or_where("first_name","=","James");
            })->where("last_name","=","Smith");

        $this->assertEquals('SELECT * FROM "users" WHERE ("first_name" = ? OR "first_name" = ?) AND "last_name" = ?', $db->toSql());
        $this->assertEquals(array("Derek","James","Smith"), $db->getBindings());
    }

    public function testNestedWhereQueryWithExternalData()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $first_names = array("Derek","James","Nick");
        $db->select('*')->from('users')->where(function($query) use ($first_names)
            {
                $query->where("first_name","IN",$first_names);
                $query->or_where("last_name","=","Smith");
            })->where("active","=","1");

        $this->assertEquals('SELECT * FROM "users" WHERE ("first_name" IN (?, ?, ?) OR "last_name" = ?) AND "active" = ?', $db->toSql());
        $this->assertEquals(array("Derek","James","Nick","Smith","1"), $db->getBindings());
    }

    public function testFullJoin()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->join("hats","users.user_id","=","hats.user_id","LEFT")->where("users.first_name","=","Derek");

        $this->assertEquals('SELECT * FROM "users" LEFT JOIN "hats" ON "users"."user_id" = "hats"."user_id" WHERE "users"."first_name" = ?', $db->toSql());
        $this->assertEquals(array("Derek"), $db->getBindings());
    }

    public function testJoinByString()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->join("hats","users.user_id = hats.user_id")->where("users.first_name","=","Derek");

        $this->assertEquals('SELECT * FROM "users" JOIN "hats" ON users.user_id = hats.user_id WHERE "users"."first_name" = ?', $db->toSql());
        $this->assertEquals(array("Derek"), $db->getBindings());
    }

    public function testJoinByStringWithJoinType()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->join("hats","users.user_id = hats.user_id","LEFT")->where("users.first_name","=","Derek");

        $this->assertEquals('SELECT * FROM "users" LEFT JOIN "hats" ON users.user_id = hats.user_id WHERE "users"."first_name" = ?', $db->toSql());
        $this->assertEquals(array("Derek"), $db->getBindings());
    }

    public function testHavingString()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->having("users.total_cats > 10");

        $this->assertEquals('SELECT * FROM "users" HAVING "users"."total_cats" > ?', $db->toSql());
        $this->assertEquals(array("10"), $db->getBindings());
    }

    public function testHavingParams()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->having("users.total_cats",">","10");

        $this->assertEquals('SELECT * FROM "users" HAVING "users"."total_cats" > ?', $db->toSql());
        $this->assertEquals(array("10"), $db->getBindings());
    }

    public function testGroupByString()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->groupBy("last_name");

        $this->assertEquals('SELECT * FROM "users" GROUP BY "last_name"', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testGroupByStringMultiple()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->groupBy("last_name, first_name");

        $this->assertEquals('SELECT * FROM "users" GROUP BY "last_name", "first_name"', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testGroupByArray()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->groupBy(array("last_name","first_name"));

        $this->assertEquals('SELECT * FROM "users" GROUP BY "last_name", "first_name"', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testOrderByParams()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->orderBy("last_name", "ASC");

        $this->assertEquals('SELECT * FROM "users" ORDER BY "last_name" ASC', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testOrderByParamsMultiple()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->orderBy("last_name", "ASC")->orderBy("first_name", "DESC");

        $this->assertEquals('SELECT * FROM "users" ORDER BY "last_name" ASC, "first_name" DESC', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testOrderByString()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->orderBy("last_name ASC");

        $this->assertEquals('SELECT * FROM "users" ORDER BY "last_name" ASC', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testOrderByArrayOfStrings()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->orderBy(array("last_name ASC","first_name DESC"));

        $this->assertEquals('SELECT * FROM "users" ORDER BY "last_name" ASC, "first_name" DESC', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testOrderByCommaDelimitedString()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->orderBy("last_name ASC, first_name DESC");

        $this->assertEquals('SELECT * FROM "users" ORDER BY "last_name" ASC, "first_name" DESC', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testLimitNoOffsetQuery()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->limit(1);

        $this->assertEquals('SELECT * FROM "users" LIMIT 0, 1', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testLimitWithOffsetQuery()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')->from('users')->limit(5,1);

        $this->assertEquals('SELECT * FROM "users" LIMIT 5, 1', $db->toSql());
        $this->assertEquals(array(), $db->getBindings());
    }

    public function testUpdateQueryWithArray()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->from('users')->where("user_id","=",5)->update(array("last_name"=>"Smith","first_name"=>"Derek"));

        $this->assertEquals('UPDATE "users" SET "last_name" = ?, "first_name" = ? WHERE "user_id" = ?', $db->toSql());
        $this->assertEquals(array('Smith','Derek','5'), $db->getBindings());
    }

    public function testUpdateQueryWithMethods()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->from('users')->where("user_id","=",5)->set("last_name","Smith")->set("first_name","Derek")->update();

        $this->assertEquals('UPDATE "users" SET "last_name" = ?, "first_name" = ? WHERE "user_id" = ?', $db->toSql());
        $this->assertEquals(array('Smith','Derek','5'), $db->getBindings());
    }

    public function testInsertQueryWithArray()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->from('users')->insert(array("last_name"=>"Smith","first_name"=>"Derek"));

        $this->assertEquals('INSERT INTO "users" ("last_name", "first_name") VALUES (?, ?)', $db->toSql());
        $this->assertEquals(array('Smith','Derek'), $db->getBindings());
    }

    public function testDeleteQuery()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->delete()->from('users')->where('user_id','=',5);

        $this->assertEquals('DELETE FROM "users" WHERE "user_id" = ?', $db->toSql());
        $this->assertEquals(array('5'), $db->getBindings());
    }

    public function testSelectOrderOfOperations()
    {
        $db = new juicyORM\Database\Drivers\DbDriver;
        $db->select('*')
            ->from('users')
            ->orderBy("users.last_name","ASC")
            ->groupBy("users.last_name")
            ->where("users.first_name","=","Nick")
            ->having("users.last_name","=","Smith")
            ->join("hats","users.user_id","=","hats.user_id");

        $this->assertEquals('SELECT * FROM "users" JOIN "hats" ON "users"."user_id" = "hats"."user_id" WHERE "users"."first_name" = ? GROUP BY "users"."last_name" HAVING "users"."last_name" = ? ORDER BY "users"."last_name" ASC', $db->toSql());
        $this->assertEquals(array('Nick', 'Smith'), $db->getBindings());
    }
}