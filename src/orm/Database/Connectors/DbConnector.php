<?php

namespace orm\Database\Connectors;

use PDO;

class DbConnector
{
	protected $options = array(
		PDO::ATTR_CASE => PDO::CASE_NATURAL,
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
		PDO::ATTR_STRINGIFY_FETCHES => false,
		PDO::ATTR_EMULATE_PREPARES => false,
	);

	public function getOptions(array $config)
	{
		$options = isset($config['options'])
			? $config['options']
			: array();

		return array_diff_key($this->options, $options) + $options;
	}

	protected function createConnection($dsn, array $config, array $options)
	{
		$username = isset($config['username'])?$config['username']:null;
		$password = isset($config['password'])?$config['password']:null;
		try {
			return new PDO($dsn, $username, $password, $options);
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
	}
}