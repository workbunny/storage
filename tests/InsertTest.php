<?php
declare(strict_types=1);

namespace Tests;


class InsertTest extends BaseTestCase
{

    public function testInsertSingle()
    {

        $this->driver()->insert("account", [
            "id" => 1,
            "name" => "test"
        ]);

        $this->assertEquals(
            $this->_expectedQuery(<<<doc
INSERT INTO `account` (`id`, `name`) VALUES (1, 'test')
doc)
            ,
            $this->driver()->last(true)[0]
        );
    }

    public function testInsertMulti()
    {
        $this->driver()->insert("account", [
            [
                "id" => 1,
                "name" => "test1"
            ],
            [
                "id" => 2,
                "name" => "test2"
            ]
        ]);

        $this->assertEquals(
            $this->_expectedQuery(<<<doc
INSERT INTO `account` (`id`, `name`) VALUES (1, 'test1'), (2, 'test2')
doc)
            ,
            $this->driver()->last(true)[0]
        );
    }
}
