<?php

use miladm\table\Connection;
use miladm\table\Result;
use miladm\table\Table;

include "vendor/autoload.php";

class MainConnection extends Connection
{
    public $host = "127.0.0.1";
    public $databaseName = "sample";
    public $user = 'root';
    public $password = 'root';
}

class User extends Table
{
    public function connection()
    {
        return new MainConnection;
    }

    public function tableName()
    {
        return 'user';
    }

    public function service()
    {
        return $this->join(new Service, 'user');
    }

    public function fullService(): User
    {
        $service = new Service('salad');
        $service = $service->price()->transaction();
        return $this->leftJoin($service, 'user');
    }

    public function comments(): User
    {
        return $this->join(new Comments, 'user');
    }
}

class Service extends Table
{
    public function connection()
    {
        return new MainConnection;
    }

    public function tableName()
    {
        return 'service';
    }

    public function price(): Service
    {
        return $this->join(new price, 'service');
    }

    public function transaction(): Service
    {
        return $this->join(new Transaction, 'service');
    }
}

class price extends Table
{
    public function connection()
    {
        return new MainConnection;
    }

    public function tableName()
    {
        return 'price';
    }
}

class Comments extends Table
{
    public function connection()
    {
        return new MainConnection;
    }

    public function tableName()
    {
        return 'comments';
    }
}

class Transaction extends Table
{
    public function connection()
    {
        return new MainConnection;
    }

    public function tableName()
    {
        return 'transaction';
    }
}

$u = new User();
$u->trace()
    ->where([
        'user' => [
            'key' => 12,
            'age' => 15
        ],
        "post" => [
            'title' => 'hello world!'
        ]
    ])
    // ->where('key=?', [1])
    // ->where('a.`key`=?', [2])
    // ->where('a.key=?&b.z=?', [3, 4])
    ->select();
// $r = $u->trace(0)
//     ->select();

// $r[0]->name = "milad";


// $s = new Service();
// $p = new Price();

// $u = new User();

// $a = $s->join($u, 'user')->leftJoin('price', 'service.id=price.service');
// $r = $a->select();
// // $r = $p->join($a, 'service')->select();
// die(json_encode(
//     $r,
//     JSON_PRETTY_PRINT
// ));

// $r = $u->query('insert into `user` (`name`, `email`) values (?, ?)', ['milad', 'm@gmail.com']);
// $r = $u->query('select * from `user`');
// $r = $u->insert(['name' => 'mahyar', 'email' => 'ma@gmail.com']);
// $r = $u
//     ->where('name=?', ['milad'])->where("id<? | id<?", [8])
//     ->update(['name' => 'milad', "age" => 10]);
// $r = $u
//     ->update(['name' => 'milad', "age" => 12]);
// $r = $u->where('id>=?', [1])->orderDesc()->group('age')->having("_c>3")->limit(1, 0)->select("*, count(*) as _c");
// $r = $u->where('id>=?', [1])->orderDesc()->group('age')->having("_c>3")->limit(1, 0)->select("*, count(*) as _c");

// $r = $s->insert([
//     'name' => 'sample',
//     'duration_day' => 13,
//     'data' => 'dsaf'
// ]);
