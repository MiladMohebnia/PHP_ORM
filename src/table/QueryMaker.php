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

	public static function update($table, $data): UpdateDataType
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
		if ($table->leftJoin && is_array($table->leftJoin)) {
			trigger_error('check here please');
			foreach ($table->leftJoin as $leftJoinTable) {
				$leftJoinQuery .= ' LEFT JOIN ' . $leftJoinTable[0] . ' ON ' . $leftJoinTable[1];
			}
		}
		$scopeString = "";
		if (($table->index["condition"] ?? false)) {
			$scopeString = self::index($table->index["condition"]);
		}
		$query = "UPDATE `" . $table->name() . "` $leftJoinQuery SET $sets " . $scopeString;
		$updateData = new UpdateDataType;
		$updateData->string = $query;
		$updateData->data = $data;
		return $updateData;
	}

	public static function index($condition)
	{
		$placeHolders = [
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
		foreach ($placeHolders as $key => $value)
			$condition = str_replace($key, $value, $condition);
		foreach ($matches[0] as $key => $value) {
			$condition = str_replace("[[$key]]", $value, $condition);
		}
		return $condition;
	}

	public static function select($table, $cols): SelectDataType
	{
		if (!$cols) {
			$cols = "*";
		}
		if (is_array($cols)) {
			$cols = Parse::selectCols($cols);
		}
		$tables = Parse::selectTables($table);
		$condition = $table->index['condition'] ?? '';
		$scopeString =  self::index($condition);
		if (is_array($table->control)) {
			$group = $table->control['group'] ?? '';
			$having = $table->control['having'] ?? '';
			$order = $table->control['order'] ?? '';
			$limit = $table->control['limit'] ?? '';
			$scopeString .= $group . $having . $order . $limit;
		}
		$query = "SELECT $cols FROM $tables " . $scopeString;
		$selectData = new SelectDataType;
		$selectData->string = $query;
		return $selectData;
	}


	public static function selectTables(&$table)
	{
		$tables = "`" . $table->name() . "`";
		if ($table->as)
			$tables .= " as " . $table->as;

		#adding left joins
		$leftJoin = $table->leftJoin ? self::leftJoins($table->leftJoin) : "";
		$tables .= " " . @$leftJoin;
		return $tables = static::mkt($table->parentNameList) . $tables;
	}

	private static function mkt(&$pnames)
	{
		$tables = false;
		if (!$pnames)
			return;
		foreach ($pnames as $val) {
			$tables .= "`" . $val["name"] . "`";
			if ($val["as"])
				$tables .= " as " . $val["as"];

			#adding left joins
			$leftJoin = $val["leftJoin"] ? self::leftJoins($val["leftJoin"]) : "";
			$tables .= " " . @$leftJoin;

			#preparing for next table
			$tables .= ", ";
		}
		return $tables;
	}

	public static function selectCols($cols)
	{
		$columns = "";
		foreach ($cols as $key => $val) {
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