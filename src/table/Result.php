<?php

namespace miladm\table;

class Result
{
    private $cleanData = [];

    private $dirtyData = [];

    private $table = null;

    function __set($name, $value)
    {
        return $this->dirtyData[$name] = $value;
    }

    function __get($name)
    {
        return $this->dirtyData[$name] ?? ($this->cleanData[$name] ?? null);
    }

    function __isset($name)
    {
        return isset($this->dirtydata[$name]) ?: isset($this->cleandata[$name]);
    }

    function __construct(Table $table, $data)
    {
        $this->table = $table;
        $this->cleanData = (array) $data;
    }

    public function save()
    {
        if (!$this->isAssoc($this->dirtyData)) {
            return false;
        }
        return $this->table->trace(0)->update($this->dirtyData);
    }

    private function isAssoc(array $arr)
    {
        if (array() === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
