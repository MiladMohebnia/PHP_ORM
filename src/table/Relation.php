<?php

namespace miladm\table;

class Relation
{
    public $mainTable;

    public $tableList = [];

    public $mappingList = [];

    public function __construct($table)
    {
        $this->mainTable = $table->name();
    }

    public function join(Table $table, $mapping)
    {
        $this->tableList[] = $table->name();
        $this->mappingList[] = $mapping;
        if ($table->relation) {
            $this->tableList = array_merge($this->tableList, $table->relation->tableList);
            $this->mappingList = array_merge($this->mappingList, $table->relation->mappingList);
        }
    }
}
