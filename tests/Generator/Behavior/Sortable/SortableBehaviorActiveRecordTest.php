<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Sortable;

/**
 * Tests for SortableBehavior class with ActiveRecordTrait
 *
 * @author Massimiliano Arione
 * @author Cristiano Cinotti
 */
class SortableBehaviorActiveRecordTest extends TestCaseActiveRecord
{
    public function setUp(): void
    {
        parent::setUp();

        $this->populateEntity11();
    }
    
    public function testIsFirst()
    {
        $first = \SortableEntity11Query::create()->findOneByRank(1);
        $middle = \SortableEntity11Query::create()->findOneByRank(2);
        $last = \SortableEntity11Query::create()->findOneByRank(4);
        $this->assertTrue($first->isFirst(), 'isFirst() returns true for the first in the rank');
        $this->assertFalse($middle->isFirst(), 'isFirst() returns false for a middle rank');
        $this->assertFalse($last->isFirst(), 'isFirst() returns false for the last in the rank');
    }

    public function testIsLast()
    {
        $first = \SortableEntity11Query::create()->findOneByRank(1);
        $middle = \SortableEntity11Query::create()->findOneByRank(2);
        $last = \SortableEntity11Query::create()->findOneByRank(4);
        $this->assertFalse($first->isLast(), 'isLast() returns false for the first in the rank');
        $this->assertFalse($middle->isLast(), 'isLast() returns false for a middle rank');
        $this->assertTrue($last->isLast(), 'isLast() returns true for the last in the rank');
    }

    public function testGetNext()
    {
        $t = \SortableEntity11Query::create()->findOneByRank(3);
        $this->assertEquals(4, $t->getNext()->getRank(), 'getNext() returns the next object in rank');

        $t = \SortableEntity11Query::create()->findOneByRank(4);
        $this->assertNull($t->getNext(), 'getNext() returns null for the last object');
    }

    public function testGetPrevious()
    {
        $t = \SortableEntity11Query::create()->findOneByRank(3);
        $this->assertEquals(2, $t->getPrevious()->getRank(), 'getPrevious() returns the previous object in rank');

        $t = \SortableEntity11Query::create()->findOneByRank(1);
        $this->assertNull($t->getPrevious(), 'getPrevious() returns null for the first object');
    }

    public function testInsertAtRank()
    {
        $t = new \SortableEntity11();
        $t->setTitle('new');
        $t->insertAtRank(2);
        $this->assertEquals(2, $t->getRank(), 'insertAtRank() sets the position');
        $this->assertTrue($t->isNew(), 'insertAtRank() doesn\'t save the object');
        $t->save();
        $expected = [1 => 'row1', 2 => 'new', 3 => 'row2', 4 => 'row3', 5 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'insertAtRank() shifts the entire suite');
    }

    public function testInsertAtMaxRankPlusOne()
    {
        $t = new \SortableEntity11();
        $t->setTitle('new');
        $t->insertAtRank(5);
        $this->assertEquals(5, $t->getRank(), 'insertAtRank() sets the position');
        $t->save();
        $expected = [1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4', 5 => 'new'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'insertAtRank() can insert an object at the end of the list');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testInsertAtNegativeRank()
    {
        $t = new \SortableEntity11();
        $t->insertAtRank(0);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testInsertAtOverMaxRank()
    {
        $t = new \SortableEntity11();
        $t->insertAtRank(6);
    }

    public function testInsertAtBottom()
    {
        $t = new \SortableEntity11();
        $t->setTitle('new');
        $t->insertAtBottom();
        $this->assertEquals(5, $t->getRank(), 'insertAtBottom() sets the position to the last');
        $this->assertTrue($t->isNew(), 'insertAtBottom() doesn\'t save the object');
        $t->save();
        $expected = [1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4', 5 => 'new'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'insertAtBottom() does not shift the entire suite');
    }

    public function testInsertAtTop()
    {
        $t = new \SortableEntity11();
        $t->setTitle('new');
        $t->insertAtTop();
        $this->assertEquals(1, $t->getRank(), 'insertAtTop() sets the position to 1');
        $this->assertTrue($t->isNew(), 'insertAtTop() doesn\'t save the object');
        $t->save();
        $expected = [1 => 'new', 2 => 'row1', 3 => 'row2', 4 => 'row3', 5 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'insertAtTop() shifts the entire suite');
    }

    public function testMoveToRank()
    {
        $t2 = \SortableEntity11Query::create()->findOneByRank(2);
        $t2->moveToRank(3);
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move up');
        $t2->moveToRank(1);
        $expected = [1 => 'row2', 2 => 'row1', 3 => 'row3', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move to the first rank');
        $t2->moveToRank(4);
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move to the last rank');
        $t2->moveToRank(2);
        $expected = [1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move down');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testMoveToNewObject()
    {
        $t = new \SortableEntity11();
        $t->moveToRank(2);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testMoveToNegativeRank()
    {
        $t = \SortableEntity11Query::create()->findOneByRank(2);
        $t->moveToRank(0);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testMoveToOverMaxRank()
    {
        $t = \SortableEntity11Query::create()->findOneByRank(2);
        $t->moveToRank(5);
    }

    public function testSwapWith()
    {
        $t2 = \SortableEntity11Query::create()->findOneByRank(2);
        $t4 = \SortableEntity11Query::create()->findOneByRank(4);
        $t2->swapWith($t4);
        $expected = [1 => 'row1', 2 => 'row4', 3 => 'row3', 4 => 'row2'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'swapWith() swaps ranks of the two objects and leaves the other ranks unchanged');
    }

    public function testMoveUp()
    {
        $t3 = \SortableEntity11Query::create()->findOneByRank(3);
        $res = $t3->moveUp();
        $this->assertEquals($t3, $res, 'moveUp() returns the current object');
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveUp() swaps ranks with the object of higher rank');
        $t3->moveUp();
        $expected = [1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveUp() swaps ranks with the object of higher rank');
        $res = $t3->moveUp();
        $expected = [1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveUp() changes nothing when called on the object at the top');
    }

    public function testMoveDown()
    {
        $t2 = \SortableEntity11Query::create()->findOneByRank(2);
        $res = $t2->moveDown();
        $this->assertEquals($t2, $res, 'moveDown() returns the current object');
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveDown() swaps ranks with the object of lower rank');
        $t2->moveDown();
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveDown() swaps ranks with the object of lower rank');
        $res = $t2->moveDown();
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveDown() changes nothing when called on the object at the bottom');
    }

    public function testMoveToTop()
    {
        $t3 = \SortableEntity11Query::create()->findOneByRank(3);
        $res = $t3->moveToTop();
        $this->assertEquals($t3, $res, 'moveToTop() returns the current oobject');
        $expected = [1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToTop() moves to the top');
        $res = $t3->moveToTop();
        $expected = [1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToTop() changes nothing when called on the top node');
    }

    public function testMoveToBottom()
    {
        $t2 = \SortableEntity11Query::create()->findOneByRank(2);
        $res = $t2->moveToBottom();
        $this->assertEquals($t2, $res, 'moveToBottom() returns the current object');
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToBottom() moves to the bottom');
        $t2->moveToBottom();
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToBottom() changes nothing when called on the bottom node');
    }

    public function testRemoveFromList()
    {
        $t2 = \SortableEntity11Query::create()->findOneByRank(2);
        $res = $t2->removeFromList();
        $this->assertTrue($res instanceof \SortableEntity11, 'removeFromList() returns the current object');
        $this->assertNull($res->getRank(), 'removeFromList() resets the object\'s rank');
        $expected = [1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4'];
        $this->getRepository('\SortableEntity11')->getConfiguration()->getSession()->clearFirstLevelCache();
        $this->assertEquals($expected, $this->getFixturesArray(), 'removeFromList() does not change the list until the object is saved');
        $t2->save();
        $expected = [null => 'row2', 1 => 'row1', 2 => 'row3', 3 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'removeFromList() changes the list once the object is saved');
    }
}