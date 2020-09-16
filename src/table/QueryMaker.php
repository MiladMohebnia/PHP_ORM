<?php

namespace miladm\table;

use miladm\table\query\InsertDataType;
use miladm\table\query\SelectDataType;
use miladm\table\query\UpdateDataType;

class QueryMaker
{
	public static function insert($table, $data): InsertDataType
	{
		if (count($table->base)) {
			$data = array_merge($table->base, $data);
		}
		$first = true;
		$args = "";
		$vals = "";
		foreach ($data as $key => $value) {
			if (!$first) {
				$args .= ", ";
				$vals .= ", ";
			}
			$first = false;
			if ($key[0] == "`") {
				die(trigger_error("don't add ` to column names we handle it!"));
			}
			$key = str_replace('.', '`.`', $key);
			$args .= "`$key`";
			$vals .= "?";
			$executeData[] = $value;
		}
		$insertQueryData = new InsertDataType;
		$insertQueryData->string = "INSERT INTO `" . $table->name() . "` ($args) VALUES ($vals)";
		$insertQueryData->data = $executeData;
		return $insertQueryData;
	}

	public static function update(Table $table, $data): UpdateDataType
	{
		$first = true;
		$sets = "";
		foreach ($data as $key => $value) {
			if (!$first) {
				$sets .= ", ";
			}
			if ($key[0] == "`") {
				die(trigger_error("don't add ` to column names we handle it!"));
			}
			$first = false;
			$key = str_replace('.', '`.`', $key);
			$sets .= "`$key` = ?";
			$executeData[] = $value;
		}
		$data = $executeData;
		$leftJoinQuery = '';
		// if ($table->leftJoin && is_array($table->leftJoin)) {
		// 	trigger_error('check here please');
		// 	foreach ($table->leftJoin as $leftJoinTable) {
		// 		$leftJoinQuery .= ' LEFT JOIN ' . $leftJoinTable[0] . ' ON ' . $leftJoinTable[1];
		// 	}
		// }
		$scopeString = "";
		if (($table->index["condition"] ?? false)) {
			$scopeString = self::index($table);
		}
		$query = "UPDATE `" . $table->name() . "` " . $table->getCoverNameString() . " $leftJoinQuery SET $sets " . $scopeString;
		$updateData = new UpdateDataType;
		$updateData->string = $query;
		$updateData->data = $data;
		return $updateData;
	}

	public static function index($table)
	{
		$condition = $table->index["condition"] ?? '';
		$placeHolders = [
			"__key__" => $table->key,
			"=" => " = ",
			"><" => " BETWEEN ",
			"!" => " NOT ",
			"&" => " AND ",
			"|" => " OR ",
			">" => " > ",
			"<" => " < ",
			":" => " LIKE ",
			" >  = " => " >= ",
			" <  = " => " <= ",
		];
		preg_match_all(
			"/'([^'])*'/",
			$condition,
			$matches
		);
		foreach ($matches[0] as $key => $value) {
			$condition = str_replace($value, "[[$key]]", $condition);
		}
		foreach ($placeHolders as $key => $value) {
			$condition = str_replace($key, $value, $condition);
		}
		foreach ($matches[0] as $key => $value) {
			$condition = str_replace("[[$key]]", $value, $condition);
		}
		return $condition;
	}

	public static function select($table, $columnList): SelectDataType
	{
		if (!$columnList) {
			$columnList = "*";
		}
		if (is_array($columnList)) {
			$columnList = self::selectcolumnList($columnList);
		}
		$tables = self::selectTables($table);
		$scopeString =  self::index($table);
		if (is_array($table->control)) {
			$group = $table->control['group'] ?? '';
			$having = $table->control['having'] ?? '';
			$order = $table->control['order'] ?? '';
			$limit = $table->control['limit'] ?? '';
			$scopeString .= $group . $having . $order . $limit;
		}
		$query = "SELECT $columnList FROM $tables " . $scopeString;
		$selectData = new SelectDataType;
		$selectData->string = $query;
		return $selectData;
	}


	public static function selectTables(Table &$table)
	{
		$tables = "`" . $table->name() . "` " . $table->getCoverNameString();
		$tables .= $table->relation ? $table->relation->makeRelationString() : '';
		// $leftJoin = $table->leftJoin ? self::leftJoins($table->leftJoin) : "";
		// $tables .= " " . @$leftJoin;
		return $tables;
	}


	public static function selectcolumnList($columnList)
	{
		$columns = "";
		foreach ($columnList as $key => $val) {
			if ($key > 0)
				$columns .= ", ";
			foreach ([
				"/^([a-zA-Z0-9\_\-]*)\.([a-zA-Z0-9\_\-]*)[ ]+as[ ]+([a-zA-Z0-9\_]*)$/" => "`$1`.`$2` as `$3`",
				"/^([a-zA-Z0-9\_\-]*)[ ]+as[ ]+([a-zA-Z0-9\_]*)$/" => "`$1` as `$2`",
				"/^([a-zA-Z0-9\_\-]*)\.([a-zA-Z0-9\_\-]*)$/" => "`$1`.`$2`",
				"/^([a-zA-Z0-9\_\-]*)$/" => "`$1`",
			] as $regex => $replace) {
				$val = preg_replace($regex, $replace, $val);
			}
			$columns .= $val;
		}
		return $columns;
	}

	/*
		- left join added 16 jul 2016
	*/
	public static function leftJoins($leftJoins)
	{
		$string = "";
		foreach ($leftJoins as $value) {
			$string .= " LEFT JOIN " . $value[0] . " on " . $value[1] . " ";
		}
		return $string;
	}
}
