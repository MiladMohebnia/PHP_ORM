<?php

namespace miladm\table;

class Relation
{
    public $mainTable;

    public $mainTableCoverNameString;

    public $joinTableList = [];

    public $mappingList = [];

    public function __construct(Table $table)
    {
        $this->mainTable = $table->name();
        $this->mainTableCoverNameString = $table->getCoverNameString();
    }

    public function join(Table $table, $mapping)
    {
        $this->joinTableList[] = (object) [
            "type" => "JOIN",
            "table" => $table->relation ?? $table->name() . $table->getCoverNameString()
        ];
        $this->mappingList[] = $mapping;
    }

    public function leftJoin(Table $table, $mapping)
    {
        $this->joinTableList[] = (object)[
            "type" => "LEFT JOIN",
            "table" => $table->relation ?? $table->name() . $table->getCoverNameString()
        ];
        $this->mappingList[] = $mapping;
    }

    public function makeRelationString()
    {
        $string = '';
        foreach ($this->joinTableList as $key => $table) {
            if (is_string($table->table)) {
                $string .= $this->buildJoinQuery($table, $key);
            } else {
                $joinQuery = $this->buildJoinQueryFromObject($table, $key);
                $string .=  $joinQuery[0] . $table->table->makeRelationString() . $joinQuery[1];
            }
        }
        return $string;
    }

    private function buildJoinQuery($table, $key)
    {
        return "$table->type `$table->table` on " . $this->mappingList[$key][0] . " = " . $this->mappingList[$key][1] . " ";
    }

    private function buildJoinQueryFromObject($table, $key)
    {
        return [
            $table->type  . "(" . "`" . $table->table->mainTable . "` " . $table->table->mainTableCoverNameString,
            ") on " . $this->mappingList[$key][0] . " = " . $this->mappingList[$key][1] . " "
        ];
    }
}
