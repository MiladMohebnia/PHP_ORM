[Home](../readme.md)

# Database (table) introduction
This hook takes care of everything you need with database and currently it works with **MySQL** database.
With the use of **PDO** communication, it will lower the threat of SQL-injection to almost zero.

# Configuration and Installation
For now,For now, this hook works with only one database each project. to setup the connection configurations you need to add connection data to _conf_ class at _set/configs.php_.

```php
class conf
{
	protected $connection = [
		"host" => "{{host address}}",
		"name" => "{{table name}}",
		"user" => "{{username}}",
		"pass" => "{{password}}"
	];

	...
}
```
To define table to project you need to create a class by the name of table and the class has to extens **table** class that is already exists all over the project.

second step is to define **name** and the **key** of table inside the class as it shows below:

```php
class {{table real or fake name}} extneds table
{
	public $name = "{{table real name}}";
	protected $key = "{{the key name of this table}}";
}
```
**NOTE:** by default the key name is `id` so if it's the same with your table key name you don't have to set it.

To create the query you need to create a function of your table then create the query.

```php
class user_table extends \Table
{
	public $name = "user";
}

$userTable = new user_table;
$userTable->select();// this will select all records from user table

// equal Query : SELECT * FROM `user` WHERE 1
```
#configurations
fetch mode can be configured as ```array``` and ```object```. by default fetch mode is ```object``` and to change that
you can simply run code below:

```php
$userTable->fetchArray()->select();
```


# Functions

| name 					| description 	|
| --- 					| --- 	|
| `$table->insert( ... )` | inserting data to table |
| `$table->where( ... )` | To setup the condition of query. |
| `$table->select( ... )` | make the selection from the current table and with the set condition |
| `$table->count()` |  return the count of records with the set condition |
| `$table->update( ... )` | updates the record(s) with the set condition |
| `$table->delete()` |  delete the record(s) with the set condition |
