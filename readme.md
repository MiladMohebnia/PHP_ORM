# Database (table) introduction

This hook takes care of everything you need with database and currently it works with **MySQL** database.
With the use of **PDO** communication, it will lower the threat of SQL-injection to almost zero.

# Configuration and Installation

## create

For each table you have to create a Class extends from ``Table` class.

```php
use miladm\table\Table;

class User extends Table {

}
```

there are abstract methods you have to config as below

| method     | description                                                 |
| ---------- | ----------------------------------------------------------- |
| connection | here we register the database connection and configurations |
| tableName  | the name of current table in string                         |

```php
class User extends Table {
	public function connection() {
		return new MainConnection;
	}

	public function tableName() {
		return 'user';
	}
}
```

## setup actions before using the table

there's a method called ``init` to setup actions before using this table;

```php
	public function init()
	{
		$this->leftJoin( .... );
	}
```

## Connection Class

```php
use miladm\table\Connection;

class MainConnection extends Connection
{
    public $host = "127.0.0.1";
    public $databaseName = "sample";
    public $user = 'root';
    public $password = 'root';
}
```

here's the structure of creating connection and to assign a table to a connection

```php
class User extends Table {
	...

	public function connection() {
		return new MainConnection;
	}

	....
}
```

**NOTE:** by default the key name is `id` so if it's the same with your table key name you don't have to set it.

## set default Key

```php
    public function key()
    {
        return 'id';
	}
```

you can change `'id` as you wish and your database structure is.

# Query

To create the query you need to create a function of your table then create the query.

```php
$userTable = new user;
$userTable->select();// this will select all records from user table

// equal Query : SELECT * FROM `user` WHERE 1
```

## query methods

| method             | parameters                        | description                                                       |
| ------------------ | --------------------------------- | ----------------------------------------------------------------- |
| trace              | state(boolean)                    | will return the query string and won't run query just to trace    |
| safeMode           | state(boolean)                    |                                                                   |
| query              | query:string, data?:optional      | you can run query directly into table                             |
| insert             | data:array                        | insert data into table                                            |
| where              | condition: string\array           | set condition for current query (update or delete)                |
| delete             |                                   | delete current query (condition required)                         |
| select             | columnList:array\string           | selects from table                                                |
| count              |                                   | returns number of results                                         |
| orderDesc          | column:string\array               | setup desc order                                                  |
| order              | column:string\array, asc?:boolean | setup order of current query                                      |
| group              | column:string                     | acts as group by query                                            |
| having             | condition: string\array           | acts as having query                                              |
| limit              | count:int, startPoint?:int        | make limitation for query and you can do pagination               |
| fetchArray         |                                   | fetch the results as array                                        |
| checkIfFetchArray  |                                   | returns boolean if we are expecting array                         |
| fetchObject        |                                   | fetch results as object                                           |
| checkIfFetchObject |                                   | return boolean if we are expecting object as result               |
| name               |                                   | return this table name                                            |
| as                 | name:string                       | set a secondary name for table as alias                           |
| coverName          | name:string                       | works the same as `as` method                                     |
| join               | table:Table, mapping?:array       | join this table to other table returns Table object with join     |
| leftJoin           | table:Table, mapping?:array       | left join table to other table returns Table object with leftjoin |
| init_join          | table:Table, mapping?:array       | do join effect current object                                     |
| init_leftJoin      | table:Table, mapping?:array       | do left join effect current object                                |
| getRelation        |                                   | return relation object of current object                          |

**note**

> documentation is in progress but code talks itself. checkout the code for more.
