<?php
namespace table;

class Parse
{
	public static function insert($data , $base = false)
	{
		if ($base) 
			$data = array_merge($base, $data);
		$first = true;
		$args = "";
		$vals = "";
		foreach ($data as $key => $value) {
			if (!$first) {
				$args .= ", ";
				$vals .= ", ";
			}
			$first = false;
			if ($key[0] == "`") 
				die(trigger_error("don't add ` to column names we handle it!"));
			$key = str_replace('.', '`.`', $key);
			$args .= "`$key`";
			$vals .= "?";
			$executeData[] = $value;
		}
		return ["args" => $args, "vals" => $vals, "data" => $executeData];
	}

	public static function update($data)
	{
		$first = true;
		$sets = "";
		foreach ($data as $key => $value) {
			if (!$first) 
				$sets .= ", ";
			if ($key[0] == "`") 
				die(trigger_error("don't add ` to column names we handle it!"));
			$first = false;
			$key = str_replace('.', '`.`', $key);
			$sets .= "`$key` = ?";
			$executeData[] = $value;
		}
		return ["sets" => $sets, "data" => $executeData];
	}

	public static function index($condition)
	{
		$placeHolders = [ "=" => " = ", "><" => " BETWEEN ", "!" => " NOT " , "&" => " AND ", "|" => " OR ", ">" => " > ", "<" => " < ", ":" => " LIKE "];
		preg_match_all("/'([^'])*'/", $condition, $matches);
		foreach ($matches[0] as $key => $value) {
			$condition =str_replace($value, "[[$key]]", $condition);
		}
		foreach ($placeHolders as $key => $value)
			$condition = str_replace($key, $value, $condition);
		foreach ($matches[0] as $key => $value) {
			$condition =str_replace("[[$key]]", $value, $condition);
		}
		return $condition;
	}

	// public static function log($query)
	// {
	// 	return $_SESSION['QL'][] = $query;
	// }

	// public static function clearLog()
	// {
	// 	unset($_SESSION['QL']);
	// }

	public static function selectTables(&$table)
	{
		$tables = "`" . $table->name() . "`";
		if ($table->as) 
			$tables .= " as ". $table->as;

		#adding left joins
		$leftJoin = $table->leftJoin ? self::leftJoins($table->leftJoin) : "";
		$tables .= " " . @$leftJoin;
		return $tables = static::mkt($table->pnames) . $tables;
	}

	private static function mkt(&$pnames)
	{
		$tables = false;
		if (!$pnames) 
			return;
		foreach ($pnames as $val) {
			$tables .= "`".$val["name"]."`";
			if ($val["as"]) 
				$tables .= " as ".$val["as"];

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
			$string .= " LEFT JOIN ".$value[0]." on ".$value[1]." ";
		}
		return $string;
	}
}
