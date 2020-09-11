<?php

use miladm\table\Connection;
use miladm\table\DatabaseConfiguration;
use miladm\table\Table;

include "vendor/autoload.php";

class MainConnection extends Connection
{
    public $host = "127.0.0.1";
    public $databaseName = "sample";
    public $user = 'root';
    public $password = 'root';
}


class user extends Table
{
    public function connection()
    {
        return new MainConnection;
    }

    public function tableName()
    {
        return 'user';
    }
}

$u = new user();
// $r = $u->query('insert into `user` (`name`, `email`) values (?, ?)', ['milad', 'm@gmail.com']);
// $r = $u->query('select * from `user`');
// $r = $u->insert(['name' => 'mahyar', 'email' => 'ma@gmail.com']);
// $r = $u
//     ->where('name=?', ['milad'])->where("id<? | id<?", [8])
//     ->update(['name' => 'milad', "age" => 10]);
// $r = $u
//     ->update(['name' => 'milad', "age" => 12]);
$r = $u->where('id><?&?', [7, 9])->order('age')->select();
die(json_encode(
    $r,
    JSON_PRETTY_PRINT
));


// class book extends Table
// {
//     public function connection()
//     {
//         return new MainConnection;
//     }

//     public function tableName()
//     {
//         return 'book';
//     }
// }

// $b = new book();