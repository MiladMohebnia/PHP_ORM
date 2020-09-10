<?php
/*
Production name: Objective DataBase absed on PDO
Version: 2.0.0
Author: Milad Mohebnia
Modify date: 19/08/2014
Description:
	Communicate database as an object .. access easy, quey easy and make relations easy
Documentation:
	to setup database first need to config connection on [set/configs.php]

 */

defined('DEVMODE') ?: define('DEVMODE', false);

abstract class Table
{
	// connection
	private $connection = false;

	// the key of this table
	protected $key = "id";

	// to set data to insert or to show
	protected $data = [];

	//  if we made this table under other table's permition
	public $base = [];

	// this will be set by query index EX. where INDEX
	public $index = [];

	// join tables @ selection
	public $join = [];

	//to set query control @ selection
	public $control;

	// parent if exists
	public $parent = false;

	// parent if exists
	public $pnames = false;
	public $as = false;

	// to left join in selection
	public $leftJoin = false;
	public $fetchMode = PDO::FETCH_OBJ;

	/*----------------------configurations--------------------*/
	function __set($name, $value)
	{
		return $this->data[$name] = $value;
	}

	function __get($name)
	{
		return $this->data[$name];
	}

	function __isset($name)
	{
		return isset($this->data[$name]);
	}

	function __construct()
	{
		global $globalConnection;
		$this->key = $this->cookColumn($this->key);
		if (!$globalConnection) {
			$this->connection = new \table\Connection();
			$globalConnection = $this->connection;
		} else {
			$this->connection = $globalConnection;
		}
	}

	public function join($parent, $column = null): Table
	{
		$this->parent[] = $parent;
		$this->pnames[] = ["name" => $parent->name, "as" => $parent->as, "leftJoin" => $parent->leftJoin];
		if ($parent->pnames)
			$this->pnames = array_merge($this->pnames, $parent->pnames);
		$c = count($this->parent) - 1;
		$miniBase = [];
		if (isset($this->parent[$c]->id) && $column)
			$miniBase = ["`" . ($this->as ? $this->as : $this->name) . "`.`$column`" => $this->parent[$c]->id];
		$this->base = array_merge($this->parent[$c]->base, $miniBase);
		foreach ($this->base as $key => $value)
			$this->setWhere($key . "=" . $value);
		if ($column) {
			$this->join = array_merge($this->parent[$c]->join, $this->join);
			$this->join = array_merge($this->join, [$this->cookColumn($column) => $this->parent[$c]->key]);
		}
		return $this;
	}

	public function fetchArray(): Table
	{
		$this->fetchMode = PDO::FETCH_ASSOC;
		return $this;
	}

	/*
		- this is a new update to left join tables in 16 jul of 2016
	 */
	public function leftJoin($tableName, $joinPoint, $asName = false): Table
	{
		if (!$tableName || !$joinPoint)
			return false;
		if ($asName)
			$tableName .= " as " . $asName;
		$JP = \table\parse::index($joinPoint);
		$that = clone $this;
		$that->leftJoin[] = [$tableName, $JP];
		return $that;
	}

	/*----------------------commands--------------------*/
	public function insert($data)
	{
		if (!$this->validInput($data))
			return false;
		$that = clone $this;
		$that->eat($data);
		extract(\table\parse::insert($data, $this->base));
		$query = "INSERT INTO `$this->name` ($args) VALUES ($vals)";
		if ($that->id = $that->run($query, $data))
			return $that->where("id = ?", $that->id);
		return false;
	}

	public function update($data)
	{
		if (!$this->validInput($data))
			return false;
		extract(\table\parse::update($data));
		$this->eat($data);
		$leftJoinQuery = '';
		if ($this->leftJoin && is_array($this->leftJoin)) {
			foreach ($this->leftJoin as $leftJoinTable) {
				$leftJoinQuery .= ' LEFT JOIN ' . $leftJoinTable[0] . ' ON ' . $leftJoinTable[1];
			}
		}
		$query = "UPDATE `$this->name` $leftJoinQuery SET $sets " . $this->index["condition"];
		$executeData = array_merge($data, $this->index["variables"]);
		return $this->run($query, $executeData);
	}

	public function delete()
	{
		$query = "DELETE FROM `$this->name` " . $this->index["condition"];
		return $this->run($query, $this->index["variables"]);
	}

	public function select($cols = null)
	{

		// variables below will be replaced in extract(that->control) .. for error handling on cli we defined them.
		$group = '';
		$having = '';
		$order = '';
		$limit = '';

		$that = clone $this;
		if (is_int($cols)) {
			$that = $that->where($this->key . "=?", $cols);
			$cols = null;
		}
		if (!$cols)
			$cols = "*";
		if (is_array($cols))
			$cols = \table\parse::selectCols($cols);
		$tables = \table\parse::selectTables($that);
		foreach ($that->join as $key => $value)
			$that->setWhere($key . "=" . $value);
		if (is_array($that->control))
			extract($that->control);
		$condition = $that->index["condition"] ?? '';
		$query = "SELECT $cols FROM $tables " . $condition . $group . $having . $order . $limit;
		if ($table = $that->run($query, $that->index["variables"] ?? [])) {
			if (!isset($table[1]))
				return $table[0];
			return $table;
		}
		return false;
	}

	public function count()
	{
		return $this->select("count(*) as count")[0]->count;
	}

	/*------------------------------query controller an indexing-------------------------*/
	public function where($condition, $variables = false): Table
	{
		$that = clone $this;
		if (is_int($condition)) {
			return $that->where($this->key . "=?", $condition);
		} elseif (is_array($condition)) {

			// I can do good shit here
			/*
			 [a, b] => a and b
			 [[a], [b]] => a or b
			 [[a, c], [b, d]] => (a and c) or (b and d)
			 [[[a], [c]], [b, d]] => (a or c) or (b and d)
			 */
			$cond = "";
			$variables = [];
			$counter = 0;
			foreach ($condition as $name => $val) {
				if ($counter++ > 0)
					$cond .= "&";
				$cond .= $name . "=?";
				$variables[] = $val;
			}
			$condition = $cond;
		}
		if (!$condition)
			return $this;
		if ($variables === false)
			die(trigger_error(
				"\$variales can't be empty it's for your own security. don't insert variables in the first parameter(\$condition { $condition }) user ? mark and pass it to \$variales. for more information read about PDO preparing data."
			));
		$that = clone $this;
		if (isset($that->index["condition"]))
			$that->index["condition"] .= " AND ";
		else
			$that->index["condition"] = "WHERE ";
		$that->index["condition"] .= \table\parse::index($condition);
		$variables = is_array($variables) ? $variables : [$variables];
		if (!isset($that->index["variables"]))
			$that->index["variables"] = $variables;
		else
			$that->index["variables"] = array_merge($that->index["variables"], $variables);
		return $that;
	}

	protected function setWhere($condition): Table
	{
		if (!$condition)
			return $this;
		if ($this->index["condition"])
			$this->index["condition"] .= " AND ";
		else
			$this->index["condition"] .= "WHERE ";
		$this->index["condition"] .= \table\parse::index($condition) . " ";
		return $this;
	}

	public function order($col = false, $asc = true): Table
	{
		$that = clone $this;
		if (is_array($col)) {
			foreach ($col as $value) {
				if (is_array($value))
					$that = $that->order($value[0], $value[1]);
				else
					$that = $that->order($value);
			}
			return $that;
		}

		if (!isset($that->control['order']))
			$that->control['order'] = " ORDER BY ";
		else
			$that->control['order'] .= ", ";
		if (!$col || $col === true)
			$col = $this->key;
		else
			$col = "`" . str_replace(".", "`.`", $col) . "`";
		$that->control['order'] .= $col;
		if (!$asc)
			$that->control['order'] .= "desc ";
		return $that;
	}

	public function group($col): Table
	{
		$that = clone $this;
		if (!isset($that->control['group']))
			$that->control['group'] = " GROUP BY ";
		else
			$that->control['group'] .= ", ";
		$col = str_replace(".", "`.`", $col);
		$that->control['group'] .= "`$col` ";
		return $that;
	}

	public function having($condition, $desc = false): Table
	{
		$that = clone $this;
		if (!isset($that->control['having']))
			$that->control['having'] = " HAVING ";
		else
			$that->control['having'] .= "AND ";
		$that->control['having'] .= \table\parse::index($condition) . " ";
		return $that;
	}

	public function limit($limit): Table
	{
		if (!$limit)
			return $this;
		$that = clone $this;
		$that->control['limit'] = " LIMIT $limit ";
		return $that;
	}

	/*------------------------------proccesses-------------------------*/
	private function validInput($data)
	{
		foreach ($data as $key => $value)
			if (is_int($key))
				return false;
		return true;
	}

	private function cookColumn($column)
	{
		return "`$this->name`.`$column`";
	}

	private function run($query, $data = [])
	{
		global $globalConnection;

		#check connection
		if (!$this->connection->PDO) {
			#if not connected yet then connect
			$this->connection->connect();
			$globalConnection = $this->connection;
		}
		if (DEVMODE) {
			ob_clean();
			(var_dump($query, $data));
		}
		if (!$request = $this->connection->PDO->prepare($query))
			die(trigger_error("There's a problem in query : " . $query));

		// if there was any error then add it to erro list and return false
		// database respond error goes here
		if (!$result = $request->execute($data)) {

			// mostly error message is the third value of errorInfo()
			// if no error message then return the whole error as object
			$errorMessage = $request->errorInfo()[2] ?? $request->errorInfo();
			// \_e::set("database", $errorMessage);
			return false;
		}
		$id = (int)$this->connection->PDO->lastInsertId();
		if ($id)
			return $id; // for insertion
		$row = $request->fetchAll($this->fetchMode);
		if (is_bool($result) && $result && !$row)
			return $request->rowCount();
		if ($request->rowCount() === 1)
			$row = [$row];
		return $row ?: false;
	}

	private function eat($data)
	{
		if ($this->data = array_merge($this->data, $data))
			return true;
		return false;
	}

	/*------------------------------access-------------------------*/
	public function name()
	{
		return $this->name;
	}

	public function stat()
	{
		return true;
	}

	public function query($query, $data = null)
	{
		return $this->run($query, $data);
	}
}
