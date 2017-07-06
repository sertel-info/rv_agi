<?php

//require __DIR__.'/../vendor/autoload.php';    

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'ramal_virtual',
    'username'  => 'ipbxsertel',
    'password'  => '@ipbxsertel',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);


$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'portabilidade',
    'username'  => 'ipbxsertel',
    'password'  => '@ipbxsertel',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
], 'mysql-portabilidade');


$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'rv_logs',
    'username'  => 'ipbxsertel',
    'password'  => '@ipbxsertel',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
], 'mysql-rv_logs');


$capsule->setAsGlobal();

$capsule->bootEloquent();
