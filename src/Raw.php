<?php
declare(strict_types=1);

namespace WorkBunny\Storage;

use stdClass;

class Raw extends stdClass
{
    /**
     * @var array
     */
    public array $map;

    /**
     * @var string
     */
    public string $value;

    /**
     * @param array $map
     * @param string $value
     */
    public function __construct(array $map, string $value)
    {
        $this->map = $map;
        $this->value = $value;
    }
}