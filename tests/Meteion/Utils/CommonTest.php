<?php

declare(strict_types=1);

namespace Meteion\Utils;

use Meteion\Utils\Business\Client;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommonTest extends KernelTestCase
{
    /**
     * @var array
     */
    public $expects = [];

    protected function setUp(): void
    {
        $this->expects = [
            0.0,
            0.1,
            1.0,
            1.1,
            '0.0',
            '0.0',
            '0.1',
            '1.0',
            '1.1',
        ];
    }

    public function testIfIsFloatWorks()
    {
        foreach ($this->expects as $expect) {
            self::assertTrue(Client::isFloat($expect));
        }
    }
}
