<?php
declare(strict_types=1);
require_once './../vendor/autoload.php';

ini_set('memory_limit', '-1');

$s = new \WorkBunny\Storage\Driver([
    'filename' => ':memory:'
]);

$s->create('main',[
    "id" => [
        "INT",
        "NOT NULL",
        "PRIMARY KEY"
    ],
    "name" => [
        "VARCHAR(30)",
        "NOT NULL"
    ]
]);
$sql = [];
xhprof(function() use ($s, &$sql){

//    $s->action(function () use ($s){
//        for ($i = 0; $i < 10000; $i ++){
//            $s->insert('main', [
//                'id' => $i,
//                'name' => 'aka_' . $i
//            ]);
//        }
//    });

    for ($i = 0; $i < 10000; $i ++){
        $data[] = [
            'id' => $i,
            'name' => 'aka_' . $i
        ];
    }

    $s->insert('main', $data);

//    $sql = $s->last();
}, true);

dump($sql);


