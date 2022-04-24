<?php

namespace Meteion\Model;

class Data
{
    /**
     * @var int
     */
    public $type;

    /**
     * @var mixed
     */
    public $value;

    public function __construct(int $type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }
}
