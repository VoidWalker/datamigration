<?php

namespace Maketok\DataMigration\Action\Type;

use Maketok\DataMigration\Storage\Db\ResourceInterface;
use Maketok\DataMigration\Unit\Type\Unit;

class ReverseMoveTest extends \PHPUnit_Framework_TestCase
{
    use ServiceGetterTrait;

    public function testGetCode()
    {
        $action = new ReverseMove(
            $this->getUnitBag(),
            $this->getConfig(),
            $this->getResource()
        );
        $this->assertEquals('reverse_move', $action->getCode());
    }

    /**
     * @param string $code
     * @return Unit
     */
    public function getUnit($code)
    {
        $unit = new Unit($code);
        $unit->setReverseMoveConditions([
            "id != 5"
        ]);
        $unit->setReverseMoveOrder([
            'id'
        ]);
        $unit->setReverseMoveDirection('desc');
        return $unit;
    }

    /**
     * @param bool $expects
     * @return ResourceInterface
     */
    protected function getResource($expects = false)
    {
        $resource = $this->getMockBuilder('\Maketok\DataMigration\Storage\Db\ResourceInterface')
            ->getMock();
        if ($expects) {
            $resource->expects($this->atLeastOnce())
                ->method('move');
        }
        return $resource;
    }

    public function testProcess()
    {
        $unit = $this->getUnit('tmp');
        $action = new ReverseMove(
            $this->getUnitBag([$unit]),
            $this->getConfig(),
            $this->getResource(true)
        );
        $action->process($this->getResultMock());

        $this->assertNotEmpty($unit->getTmpTable());
    }
}
