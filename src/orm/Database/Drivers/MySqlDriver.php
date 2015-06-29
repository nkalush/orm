<?php

namespace orm\Database\Drivers;

class MySqlDriver extends DbDriver
{
	protected $identifier_wrap = '`';
	protected $supported_functions = array('*','COUNT','FROM_UNIXTIME', 'UNIX_TIMESTAMP', 'NOW', 'CONCAT', 'NULL');
}