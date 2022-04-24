<?php

namespace Meteion\Model;

class Column
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $options;

    /**
     * @var bool
     */
    public $pk;

    public function __construct(string $name, string $type, array $options = [], bool $pk = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
        $this->pk = $pk;
    }
}
