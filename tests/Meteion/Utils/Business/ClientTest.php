<?php

declare(strict_types=1);

namespace Meteion\Utils\Business;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClientTest extends KernelTestCase
{
    /**
     * @var array
     */
    public $expects = [];

    protected function setUp(): void
    {
        $this->expects = [
            '' => 'column_0',
            'Expansion' => 'expansion',
            'ClassJobCategory[0]' => 'class_job_category_0',
            'ClassJobCategory[1]' => 'class_job_category_1',
            'ClassJob{Unlock}' => 'class_job_unlock',
            'OptionalItemIsHQ{Reward}[0]' => 'optional_item_is_hq_reward_0',
            'ToDoChildLocation[0][0]' => 'to_do_child_location_0_0',
            'QuestUInt8A[0]' => 'quest_uint8a_0',
        ];
    }

    public function testIfGetColumnNameWorks()
    {
        $index = 0;
        foreach ($this->expects as $input => $expect) {
            self::assertTrue($expect === Client::getColumnName($index++, $input));
        }
    }

    public function testIfGetColumnNameWorksWithPrimaryKey()
    {
        self::assertTrue(Client::PK === Client::getColumnName(0, '#'));
    }
}
