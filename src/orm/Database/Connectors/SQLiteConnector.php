<?php

namespace orm\Database\Connectors;

class SQLiteConnector extends DbConnector
{
	public function connect(array $config)
	{
		$options = $this->getOptions($config);

		if ($config['database'] == ':memory:')
		{
			return $this->createConnection('sqlite::memory:', $config, $options);
		}

		$path = realpath($config['database']);

		if ($path === false)
		{
			throw new \InvalidArgumentException("Database does not exist.");
		}

		return $this->createConnection("sqlite:{$path}", $config, $options);
	}
}