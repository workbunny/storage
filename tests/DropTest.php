<?php
declare(strict_types=1);

namespace Tests;

class DropTest extends BaseTestCase
{

    public function testDrop()
    {
        $this->driver()->drop("account");

        $this->assertEquals(
            $this->_expectedQuery(<<<doc
DROP TABLE IF EXISTS `account`
doc)
            ,
            $this->driver()->last(true)[0]
        );
    }
}
