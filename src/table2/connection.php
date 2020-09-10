<?php 
namespace table;

class Connection
{
	private $host;
	private $name;
	private $user;
	private $pass;
	public $PDO = false;

	function __construct($connection = false)
	{
		if($connection) {
			extract($connection);
			$this->host = $host;
			$this->name = $name;
			$this->user = $user;
			$this->pass = $pass;
		} else {
			$this->host = CONNECTION_DB_HOST;
			$this->name = CONNECTION_DB_NAME;
			$this->user = CONNECTION_DB_USERNAME;
			$this->pass = CONNECTION_DB_PASSWORD;
		}
	}

	public function connect()
	{
		$dsn = "mysql:dbname=" . $this->name . ";host=" . $this->host . ";charset=UTF8";
		try {
		    $this->PDO = new \PDO( $dsn, $this->user, $this->pass, [\PDO::ATTR_EMULATE_PREPARES => false ]);
		} catch (\PDOException $e) {
		    echo 'Connection failed: ' . $e->getMessage() . PHP_EOL;
		}
		return true;
	}
}