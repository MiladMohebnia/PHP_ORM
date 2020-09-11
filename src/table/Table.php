<?php

namespace miladm\table;

use Exception;

defined('DEVMODE') ?: define('DEVMODE', false);

abstract class Table
{
    private $connection;
    private $key;
    private $tableName;
    private $fetchMode = \PDO::FETCH_OBJ;

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
    public $parentNameList = false;
    public $as = false;

    // to left join in selection
    public $leftJoin = false;

    // to set data to insert or to show
    public $data = [];

    function __set($name, $value)
    {
        return $this->data[$name] = $value;
    }

    function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    function __isset($name)
    {
        return isset($this->data[$name]);
    }

    function __construct()
    {
        $this->tableName = $this->tableName();
        $this->key = $this->cookColumn($this->key());
    }

    abstract public function connection();

    abstract public function tableName();

    public function key()
    {
        return 'id';
    }

    public function query($query, $data = null)
    {
        return $this->run($query, $data);
    }

    public function insert($data)
    {
        if (!$this->validInput($data)) {
            return false;
        }
        $that = clone $this;
        $that->mergeNewData($data);
        $insertQuery = QueryMaker::insert($that, $data);
        if ($that->id = $that->run($insertQuery->string, $insertQuery->data)) {
            return $that->where("id = ?", $that->id);
        }
        return false;
    }

    public function where($condition, $variables = false): Table
    {
        if (is_int($condition)) {
            return $this->where($this->key . "=?", $condition);
        } elseif (is_array($condition)) {
            $condition = $this->arrayConditionToString($condition);
        }
        if (!$condition) {
            return $this;
        }
        if ($variables === false) {
            die(trigger_error(
                "\$variales can't be empty it's for your own security. 
                don't insert variables in the first parameter(\$condition { $condition }) user ? mark and pass it to \$variales. 
                for more information read about PDO preparing data."
            ));
        }
        $that = clone $this;
        return $that->parseAndAddStringCondition($condition, $variables);
    }

    protected function setWhere($condition): Table
    {
        return $this->parseAndAddStringCondition($condition, false);
    }

    public function update($data)
    {
        if (!$this->validInput($data)) {
            return false;
        }
        $updateData = QueryMaker::update($this, $data);
        $this->mergeNewData($updateData->data);
        $executeData = array_merge($updateData->data, ($this->index["variables"] ?? []));
        return $this->run($updateData->string, $executeData);
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
        if (!$cols) {
            $cols = "*";
        }
        if (is_array($cols)) {
            $cols = Parse::selectCols($cols);
        }
        $tables = Parse::selectTables($that);
        foreach ($that->join as $key => $value) {
            $that->setWhere($key . "=" . $value);
        }
        if (is_array($that->control)) {
            extract($that->control);
        }
        $condition = $that->index["condition"] ?? '';
        $query = "SELECT $cols FROM $tables " . $condition . $group . $having . $order . $limit;
        if ($table = $that->run($query, $that->index["variables"] ?? [])) {
            if (!isset($table[1])) {
                return $table[0];
            }
            return $table;
        }
        return false;
    }

    public function name()
    {
        return $this->tableName;
    }

    private function arrayConditionToString($condition)
    {
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
        return $cond;
    }

    private function parseAndAddStringCondition($condition, $variables)
    {
        if (!$condition) {
            return $this;
        }
        if (isset($this->index["condition"])) {
            $this->index["condition"] .= " AND ";
        } else {
            $this->index["condition"] = " WHERE ";
        }
        $this->index["condition"] .= $condition;
        // $this->index["condition"] .= Parse::index($condition) . " ";
        if (!$variables) {
            return $this;
        }
        $variables = is_array($variables) ? $variables : [$variables];
        if (!isset($this->index["variables"])) {
            $this->index["variables"] = $variables;
        } else {
            $this->index["variables"] = array_merge($this->index["variables"], $variables);
        }
        return $this;
    }

    private function validInput($data)
    {
        foreach ($data as $key => $value)
            if (is_int($key))
                return false;
        return true;
    }

    private function mergeNewData($data)
    {
        if ($this->data = array_merge($this->data, $data))
            return true;
        return false;
    }

    private function run($query, $data = [])
    {
        $this->connection = $this->connection()->connect();
        if (!$request = $this->connection->PDO->prepare($query)) {
            die(trigger_error("There's a problem in query : " . $query));
        }

        // if there was any error then add it to erro list and return false
        // database respond error goes here
        if (!($result = $request->execute($data))) {

            // mostly error message is the third value of errorInfo()
            // if no error message then return the whole error as object
            throw new Exception($request->errorInfo()[2] ?? $request->errorInfo());
            return false;
        }

        // if it was insertion then return the Id
        $id = (int)$this->connection->PDO->lastInsertId();
        if ($id) {
            return $id; // for insertion
        }

        // if it was selection then fetch data
        $row = $request->fetchAll($this->fetchMode);
        if (is_bool($result) && $result && !$row) {

            // if there are no rows then it was update. let's return 
            // how many rows affected
            return $request->rowCount();
        }

        // if there's a single result let's return as array so data type
        // not change if there's only a single result
        if ($request->rowCount() === 1) {
            $row = [$row];
        }

        // in the end return rows but if anything's wrong let's return false;
        return $row ?: false;
    }

    private function cookColumn($column)
    {
        return "`$this->tableName`.`$column`";
    }
}
