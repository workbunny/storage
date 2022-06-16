<?php
declare(strict_types=1);

namespace Tests;

class CreateTest extends BaseTestCase
{
    public function testCreateTable()
    {
        $this->driver()->create("account", [
            "id" => [
                "INT",
                "PRIMARY KEY",
                "NOT NULL",
                "AUTOINCREMENT"
            ],
            "name" => [
                "VARCHAR(25)",
                "NOT NULL",
                "UNIQUE"
            ],
        ]);

        $this->assertEquals(
            $this->_expectedQuery(<<<doc
CREATE TABLE IF NOT EXISTS `account` (
    `id` INT PRIMARY KEY NOT NULL AUTOINCREMENT,
    `name` VARCHAR(25) NOT NULL UNIQUE
);
doc)
            ,
            $this->driver()->last(true)[0]
        );
    }

    public function testCreateTableUseIndex()
    {
        $this->driver()->create("account", [
            "id" => [
                "INT",
                "PRIMARY KEY",
                "NOT NULL",
                "AUTOINCREMENT"
            ],
            "name" => [
                "VARCHAR(25)",
                "NOT NULL",
                "UNIQUE"
            ],
        ],[
            "CREATE INDEX `account_name` ON `account` (`name`);"
        ]);

        $this->assertEquals(
            $this->_expectedQuery(<<<doc
CREATE TABLE IF NOT EXISTS `account` (
    `id` INT PRIMARY KEY NOT NULL AUTOINCREMENT,
    `name` VARCHAR(25) NOT NULL UNIQUE
);
CREATE INDEX `account_name` ON `account` (`name`);
doc)
            ,
            $this->driver()->last(true)[0]
        );
    }

    public function testCreateTableWithStringDefinition()
    {
        $this->driver()->create("account", [
            "id"   => "INT PRIMARY KEY NOT NULL AUTOINCREMENT",
            "name" => "VARCHAR(25) NOT NULL UNIQUE"
        ]);

        $this->assertEquals(
            $this->_expectedQuery(<<<doc
CREATE TABLE IF NOT EXISTS `account` (
    `id` INT PRIMARY KEY NOT NULL AUTOINCREMENT,
    `name` VARCHAR(25) NOT NULL UNIQUE
);
doc)
            ,
            $this->driver()->last(true)[0]
        );
    }
}
