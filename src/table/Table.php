<?php

namespace miladm\table;

use Exception;

defined('DEVMODE') ?: define('DEVMODE', false);

abstract class Table
{
    private $connection;

    public $key;

    private $tableName;

    public $coverName = null;

    private $fetchMode = \PDO::FETCH_ASSOC;

    // permission to create data object on \miladm\table\Result
    private $createDataObject = true;

    //  if we made this table under other table's permition
    public $base = [];

    // this will be set by query index EX. where INDEX
    public $index = [];

    // join tables @ selection
    public $relation = null;

    //to set query control @ selection
    public $control;

    public $trace = false;

    // parent if exists
    // public $parent = false;

    // parent if exists
    // public $parentNameList = false;

    // to left join in selection
    // public $leftJoin = false;

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

    function __construct($coverName = false)
    {
        $this->tableName = $this->tableName();
        if ($coverName) {
            $this->coverName = $coverName;
        }
        $this->key = $this->cookColumn($this->key());
        $this->init();
    }

    public function init()
    {
    }

    public function cookColumn($column)
    {
        if (strpos($column, '.') > 0) {
            return $column;
        }
        if ($this->coverName != null) {
            return "`$this->coverName`.`$column`";
        }
        return "`$this->tableName`.`$column`";
    }

    abstract public function connection();

    abstract public function tableName();

    public function key()
    {
        return 'id';
    }

    public function trace($state = true)
    {
        $that = clone $this;
        $that->trace = $state;
        return $that;
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
        return $that->parseAndAddStringScope($condition, $variables);
    }

    protected function setWhere($condition): Table
    {
        return $this->parseAndAddStringScope($condition, false);
    }

    public function orderDesc($column = false): Table
    {
        return $this->order($column, false);
    }

    public function order($column = false, $asc = true): Table
    {
        $that = clone $this;
        if (is_array($column)) {
            foreach ($column as $value) {
                if (is_array($value)) {
                    $that = $that->order($value[0], $value[1]);
                } else {
                    $that = $that->order($value);
                }
            }
            return $that;
        }
        if (!isset($that->control['order'])) {
            $that->control['order'] = " ORDER BY ";
        } else {
            $that->control['order'] .= ", ";
        }
        if (!$column || $column === true) {
            $column = $this->key;
        } else {
            $column = "`" . str_replace(".", "`.`", $column) . "`";
        }
        $that->control['order'] .= $column;
        if (!$asc) {
            $that->control['order'] .= " desc ";
        }
        return $that;
    }

    public function group($column): Table
    {
        $that = clone $this;
        if (!isset($that->control['group'])) {
            $that->control['group'] = " GROUP BY ";
        } else {
            $that->control['group'] .= ", ";
        }
        $column = str_replace(".", "`.`", $column);
        $that->control['group'] .= "`$column` ";
        return $that;
    }

    public function having($condition): Table
    {
        $that = clone $this;
        if (!isset($that->control['having'])) {
            $that->control['having'] = " HAVING ";
        } else {
            $that->control['having'] .= "AND ";
        }
        $that->control['having'] .= QueryMaker::index($condition) . " ";
        return $that;
    }

    public function limit(int $count, int $startPoint = 0): Table
    {
        if (!$count || !is_int($count) || !is_int($startPoint)) {
            return $this;
        }
        $that = clone $this;
        $that->control['limit'] = " LIMIT $startPoint, $count ";
        return $that;
    }

    public function update($data)
    {
        // preventing update without condition and scope
        if (!isset($this->index["condition"])) {
            return false;
        }
        if (!$this->validInput($data)) {
            return false;
        }
        $updateData = QueryMaker::update($this, $data);
        $this->mergeNewData($updateData->data);
        $executeData = array_merge($updateData->data, ($this->index["variables"] ?? []));
        return $this->run($updateData->string, $executeData);
    }

    public function fetchArray(): Table
    {
        $this->fetchMode = \PDO::FETCH_ASSOC;
        $this->createDataObject = false;
        return $this;
    }


    public function fetchObject(): Table
    {
        $this->fetchMode = \PDO::FETCH_OBJ;
        $this->createDataObject = false;
        return $this;
    }


    public function fetchDataObject(): Table
    {
        $this->fetchMode = \PDO::FETCH_ASSOC;
        $this->createDataObject = true;
        return $this;
    }


    public function select($columnList = null)
    {
        $that = clone $this;
        $selectQueryData = QueryMaker::select($that, $columnList);
        if ($result = $that->run($selectQueryData->string, $that->index["variables"] ?? [])) {
            return $result;
        }
        return false;
    }

    public function count()
    {
        return $this->select("count(*) as count")[0]->count;
    }

    public function name()
    {
        return $this->tableName;
    }

    public function as($name): Table
    {
        return $this->coverName($name);
    }

    public function coverName($name): Table
    {
        $that = clone $this;
        $that->coverName = $name;
        $that->key = $that->cookColumn($that->key());
        if ($that->relation) {
            $that->relation->mainTableCoverNameString = $that->getCoverNameString();
        }
        return $that;
    }

    public function getCoverNameString()
    {
        return $this->coverName !== null ? " as `" . $this->coverName . "`" : '';
    }

    public function join(Table $table, $mapping = null): Table
    {
        $relation = $this->getRelation();
        if (!is_array($mapping)) {
            $mapping = [$this->key, $table->cookColumn($mapping)];
        }
        $relation->join($table, $mapping);
        $that = clone $this;
        $that->relation = $relation;
        return $that;
    }

    public function leftJoin(Table $table, $mapping): Table
    {
        $relation = $this->getRelation();
        $that = clone $this;
        if (!is_array($mapping)) {
            $mapping = [$this->key, $table->cookColumn($mapping)];
        }
        $relation->leftJoin($table, $mapping);
        $that->relation = $relation;
        return $that;
    }

    public function init_join(Table $table, $mapping = null): Table
    {
        $relation = $this->getRelation();
        if (!is_array($mapping)) {
            $mapping = [$this->key, $table->cookColumn($mapping)];
        }
        $relation->join($table, $mapping);
        $this->relation = $relation;
        return $this;
    }

    public function init_leftJoin(Table $table, $mapping): Table
    {
        $relation = $this->getRelation();
        if (!is_array($mapping)) {
            $mapping = [$this->key, $table->cookColumn($mapping)];
        }
        $relation->leftJoin($table, $mapping);
        $this->relation = $relation;
        return $this;
    }

    public function getRelation(): Relation
    {
        return $this->relation ?? new Relation($this);
    }

    public function relation(): Table
    {
        return $this;
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

    private function parseAndAddStringScope($condition, $variables)
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
        if ($this->trace) {
            die(json_encode([
                "query" => $query,
                "data" => $data
            ], JSON_PRETTY_PRINT));
        }
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
        if ($request->rowCount() === 1 && !is_array($row)) {
            $row = [$row];
        }
        if (!$row) {
            return false;
        }
        if (!$this->createDataObject) {
            return $row;
        }
        $result = [];
        foreach ($row as $object) {
            if (isset($object[$this->key()])) {
                $that = $this->where("$this->key=?", [$object[$this->key()]]);
                $result[] = new Result($that, $object);
            } else {
                $result = (object) $row;
                break;
            }
        }

        // in the end return rows but if anything's wrong let's return false;
        return $result;
    }
}
