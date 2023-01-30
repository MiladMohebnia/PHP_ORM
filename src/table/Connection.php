<?php

namespace miladm\table;

use Exception;

class Connection
{
    public $host;
    public $port = 3306;
    public $databaseName;
    public $user;
    public $password;
    public $PDO;
    public static $connectionCache = [];

    public function connect()
    {
        if (get_class($this)::$connectionCache != null) {
            $connection = get_class($this)::$connectionCache;
            if (isset($connection[$this->databaseName])) {
                return  get_class($this)::$connectionCache[$this->databaseName];
            }
        };
        try {
            $this->validateConfiguration();
            $dsn = "mysql:dbname=" . $this->databaseName . ";host=" . $this->host . ";port=" . $this->port . ";charset=UTF8";
            $this->PDO = new \PDO($dsn, $this->user, $this->password, [\PDO::ATTR_EMULATE_PREPARES => false]);
        } catch (\PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage() . PHP_EOL;
        }
        get_class($this)::$connectionCache[$this->databaseName] = $this;
        return $this;
    }

    private function validateConfiguration()
    {
        if (in_array(null, [$this->host, $this->databaseName, $this->user, $this->password])) {
            throw new Exception('configuraiton not good!');
        }
    }
}
