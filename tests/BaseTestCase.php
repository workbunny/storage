<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use WorkBunny\Storage\Driver;

class BaseTestCase extends TestCase
{
    protected ?Driver $_driver = null;

    /**
     * @return void
     */
    public function setUp(): void
    {
        if(!$this->_driver){
            $this->_driver = new Driver([
                'filename' => ':memory',
                'debug'    => true
            ]);
        }
        parent::setUp();
    }

    /**
     * @return Driver|null
     */
    public function driver(): ?Driver
    {
        return $this->_driver;
    }

    protected function _expectedQuery(string $expected): string
    {
        return str_replace(["\r\n", "\r", "\n", "\t", '    '], '', $expected);
    }
}