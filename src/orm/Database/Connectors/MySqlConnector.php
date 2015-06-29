<?php

namespace orm\Database\Connectors;

class MySqlConnector extends DbConnector
{
	public function connect(array $config)
	{
		$dsn = $this->getDsn($config);
		$options = $this->getOptions($config);
		$connection = $this->createConnection($dsn, $config, $options);

		$charset = isset($config['charset'])?$config['charset']:'utf8';
		$collation = isset($config['collation'])?$config['collation']:'utf8_unicode_ci';

		$names = "set names '$charset'".
			( ! is_null($collation) ? " collate '$collation'" : '');

		$connection->prepare($names)->execute();

		if (isset($config['strict']) && $config['strict'])
		{
			$connection->prepare("set session sql_mode='STRICT_ALL_TABLES'")->execute();
		}

		return $connection;
	}

	public function getDsn(array $config)
	{
		$dsn = 'mysql:'.
			(isset($config['unix_socket'])?'unix_socket='.$config['unix_socket']:
			('host='.$config['host'].(isset($config['port'])?";port=".$config['port']:""))).
			';dbname='.$config['database'];

		return $dsn;
	}
}