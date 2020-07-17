<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\Diff\DatabaseDiff;
use Propel\Generator\Model\Diff\EntityComparator;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Tests\TestCase;

/**
 * Tests for the Entity method of the DatabaseComparator service class.
 *
 */
class DatabaseEntityComparatorTest extends TestCase
{
    protected MysqlPlatform $platform;

    public function setUp(): void
    {
        $this->platform = new MysqlPlatform();
    }

    public function testCompareSameEntities(): void
    {
        $d1 = new Database();
        $d1->setPlatform($this->platform);
        $t1 = new Entity('Foo_Entity');
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->setSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $d1->addEntity($t1);
        $t2 = new Entity('Bar');
        $d1->addEntity($t2);

        $d2 = new Database();
        $d2->setPlatform($this->platform);
        $t3 = new Entity('Foo_Entity');
        $c3 = new Field('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->setSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t3->addField($c3);
        $d2->addEntity($t3);
        $t4 = new Entity('Bar');
        $d2->addEntity($t4);

        $this->assertNull(DatabaseComparator::computeDiff($d1, $d2));
    }

    public function testCompareNotSameEntities(): void
    {
        $d1 = new Database();
        $t1 = new Entity('Foo');
        $d1->addEntity($t1);
        $d2 = new Database();
        $t2 = new Entity('Bar');
        $d2->addEntity($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2);
        $this->assertTrue($diff instanceof DatabaseDiff);
    }

    public function testCompareAddedEntity(): void
    {
        $d1 = new Database();
        $t1 = new Entity('Foo_Entity');
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->setSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $d1->addEntity($t1);

        $d2 = new Database();
        $t3 = new Entity('Foo_Entity');
        $c3 = new Field('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->setSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t3->addField($c3);
        $d2->addEntity($t3);
        $t4 = new Entity('Bar');
        $d2->addEntity($t4);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareEntities();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $databaseDiff->getAddedEntities()->size());
        $this->assertEquals(['Bar' => $t4], $databaseDiff->getAddedEntities()->toArray());
    }

    public function testCompareAddedEntitySkipSql(): void
    {
        $d1 = new Database();
        $t1 = new Entity('Foo_Entity');
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->setSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $d1->addEntity($t1);

        $d2 = new Database();
        $t3 = new Entity('Foo_Entity');
        $c3 = new Field('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->setSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t3->addField($c3);
        $d2->addEntity($t3);
        $t4 = new Entity('Bar');
        $t4->setSkipSql(true);
        $d2->addEntity($t4);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareEntities();
        $this->assertEquals(0, $nbDiffs);
    }

    public function testCompareRemovedEntity(): void
    {
        $d1 = new Database();
        $t1 = new Entity('Foo_Entity');
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->setSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $d1->addEntity($t1);
        $t2 = new Entity('Bar');
        $d1->addEntity($t2);

        $d2 = new Database();
        $t3 = new Entity('Foo_Entity');
        $c3 = new Field('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->setSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t3->addField($c3);
        $d2->addEntity($t3);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareEntities();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $databaseDiff->getRemovedEntities()->size());
        $this->assertEquals(['Bar' => $t2], $databaseDiff->getRemovedEntities()->toArray());
    }

    public function testCompareRemovedEntitySkipSql(): void
    {
        $d1 = new Database();
        $t1 = new Entity('Foo_Entity');
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->setSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $d1->addEntity($t1);
        $t2 = new Entity('Bar');
        $t2->setSkipSql(true);
        $d1->addEntity($t2);

        $d2 = new Database();
        $t3 = new Entity('Foo_Entity');
        $c3 = new Field('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->setSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t3->addField($c3);
        $d2->addEntity($t3);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareEntities();
        //$databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(0, $nbDiffs);
    }

    public function testCompareModifiedEntity(): void
    {
        $d1 = new Database();
        $t1 = new Entity('Foo_Entity');
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->setSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $c2 = new Field('Foo2');
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t1->addField($c2);
        $d1->addEntity($t1);
        $t2 = new Entity('Bar');
        $d1->addEntity($t2);

        $d2 = new Database();
        $t3 = new Entity('Foo_Entity');
        $c3 = new Field('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->setSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t3->addField($c3);
        $d2->addEntity($t3);
        $t4 = new Entity('Bar');
        $d2->addEntity($t4);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareEntities();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $databaseDiff->getModifiedEntities()->size());
        $entityDiff = EntityComparator::computeDiff($t1, $t3);
        $this->assertEquals(['Foo_Entity' => $entityDiff], $databaseDiff->getModifiedEntities()->toArray());
    }

    public function testCompareRenamedEntity(): void
    {
        $d1 = new Database();
        $t1 = new Entity('Foo_Entity');
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->setSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $d1->addEntity($t1);
        $t2 = new Entity('Bar');
        $d1->addEntity($t2);

        $d2 = new Database();
        $t3 = new Entity('Foo_Entity2');
        $c3 = new Field('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->setSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t3->addField($c3);
        $d2->addEntity($t3);
        $t4 = new Entity('Bar');
        $d2->addEntity($t4);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $dc->setWithRenaming(true);
        $nbDiffs = $dc->compareEntities();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $databaseDiff->getRenamedEntities()->size());
        $this->assertEquals(['Foo_Entity' => 'Foo_Entity2'], $databaseDiff->getRenamedEntities()->toArray());
        $this->assertTrue($databaseDiff->getAddedEntities()->isEmpty());
        $this->assertTrue($databaseDiff->getRemovedEntities()->isEmpty());
    }


    public function testCompareSeveralEntityDifferences(): void
    {
        $d1 = new Database();
        $t1 = new Entity('Foo_Entity');
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->setSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $d1->addEntity($t1);
        $t2 = new Entity('Bar');
        $c2 = new Field('Bar_Field');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $t2->addField($c2);
        $d1->addEntity($t2);
        $t11 = new Entity('Baz');
        $d1->addEntity($t11);

        $d2 = new Database();
        $t3 = new Entity('Foo_Entity');
        $c3 = new Field('Foo1');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->setSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t3->addField($c3);
        $d2->addEntity($t3);
        $t4 = new Entity('Bar2');
        $c4 = new Field('Bar_Field');
        $c4->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $t4->addField($c4);
        $d2->addEntity($t4);
        $t5 = new Entity('Biz');
        $c5 = new Field('Biz_Field');
        $c5->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t5->addField($c5);
        $d2->addEntity($t5);

        // Foo_Entity was modified, Bar was renamed, Baz was removed, Biz was added
        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareEntities();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(5, $nbDiffs);
        $this->assertTrue($databaseDiff->getRenamedEntities()->isEmpty());
        $this->assertEquals(['Bar2' => $t4, 'Biz' => $t5], $databaseDiff->getAddedEntities()->toArray());
        $this->assertEquals(['Baz' => $t11, 'Bar' => $t2], $databaseDiff->getRemovedEntities()->toArray());
        $entityDiff = EntityComparator::computeDiff($t1, $t3);
        $this->assertEquals(['Foo_Entity' => $entityDiff], $databaseDiff->getModifiedEntities()->toArray());
    }

    public function testCompareSeveralRenamedSameEntities(): void
    {
        $d1 = new Database();
        $t1 = new Entity('entity1');
        $c1 = new Field('col1');
        $c1->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t1->addField($c1);
        $d1->addEntity($t1);
        $t2 = new Entity('entity2');
        $c2 = new Field('col1');
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t2->addField($c2);
        $d1->addEntity($t2);
        $t3 = new Entity('entity3');
        $c3 = new Field('col1');
        $c3->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t3->addField($c3);
        $d1->addEntity($t3);

        $d2 = new Database();
        $t4 = new Entity('entity4');
        $c4 = new Field('col1');
        $c4->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t4->addField($c4);
        $d2->addEntity($t4);
        $t5 = new Entity('entity5');
        $c5 = new Field('col1');
        $c5->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t5->addField($c5);
        $d2->addEntity($t5);
        $t6 = new Entity('entity3');
        $c6 = new Field('col1');
        $c6->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t6->addField($c6);
        $d2->addEntity($t6);

        // entity1 and entity2 were removed and entity4, entity5 added with same fields (does not always mean its a rename, hence we
        // can not guarantee it)
        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareEntities();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(4, $nbDiffs);
        $this->assertEquals(0, $databaseDiff->getRenamedEntities()->size());
        $this->assertEquals(['entity4', 'entity5'], $databaseDiff->getAddedEntities()->keys()->toArray());
        $this->assertEquals(['entity1', 'entity2'], $databaseDiff->getRemovedEntities()->keys()->toArray());
    }

    public function testRemoveEntity(): void
    {
        $dc = new DatabaseComparator();
        $this->assertTrue($dc->getRemoveEntity());

        $dc->setRemoveEntity(false);
        $this->assertFalse($dc->getRemoveEntity());

        $dc->setRemoveEntity(true);
        $this->assertTrue($dc->getRemoveEntity());

        $d1 = new Database();
        $t1 = new Entity('Foo');
        $d1->addEntity($t1);
        $d2 = new Database();


        // with renaming false and remove false
        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false);
        $this->assertNull($diff);

        // with renaming true and remove false
        $diff = DatabaseComparator::computeDiff($d1, $d2, true, false);
        $this->assertNull($diff);

        // with renaming false and remove true
        $diff = DatabaseComparator::computeDiff($d1, $d2, false, true);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);

        // with renaming true and remove true
        $diff = DatabaseComparator::computeDiff($d1, $d2, true, true);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);
    }

    public function testExcludedEntitiesWithoutRenaming(): void
    {
        $dc = new DatabaseComparator();
        $this->assertCount(0, $dc->getExcludedEntities());

        $dc->addExcludedEntities(['foo']);
        $this->assertCount(1, $dc->getExcludedEntities());

        $d1 = new Database();
        $d2 = new Database();
        $t2 = new Entity('Bar');
        $d2->addEntity($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, ['Bar']);
        $this->assertNull($diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, ['Baz']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);

        $d1 = new Database();
        $t1 = new Entity('Foo');
        $d1->addEntity($t1);
        $d2 = new Database();
        $t2 = new Entity('Bar');
        $d2->addEntity($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, ['Bar', 'Foo']);
        $this->assertNull($diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, ['Foo']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, true, ['Bar']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);


        $d1 = new Database();
        $t1 = new Entity('Foo');
        $c1 = new Field('col1');
        $t1->addField($c1);
        $d1->addEntity($t1);
        $d2 = new Database();
        $t2 = new Entity('Foo');
        $d2->addEntity($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, ['Bar', 'Foo']);
        $this->assertNull($diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, ['Bar']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);
    }

    public function testExcludedEntitiesWithRenaming(): void
    {
        $dc = new DatabaseComparator();
        $this->assertCount(0, $dc->getExcludedEntities());

        $dc->addExcludedEntities(['foo']);
        $this->assertCount(1, $dc->getExcludedEntities());

        $d1 = new Database();
        $d2 = new Database();
        $t2 = new Entity('Bar');
        $d2->addEntity($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, true, false, ['Bar']);
        $this->assertNull($diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, true, false, ['Baz']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);

        $d1 = new Database();
        $t1 = new Entity('Foo');
        $d1->addEntity($t1);
        $d2 = new Database();
        $t2 = new Entity('Bar');
        $d2->addEntity($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, true, false, ['Bar', 'Foo']);
        $this->assertNull($diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, true, true, ['Foo']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, true, true, ['Bar']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);


        $d1 = new Database();
        $t1 = new Entity('Foo');
        $c1 = new Field('col1');
        $t1->addField($c1);
        $d1->addEntity($t1);
        $d2 = new Database();
        $t2 = new Entity('Foo');
        $d2->addEntity($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, true, false, ['Bar', 'Foo']);
        $this->assertNull($diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, true, false, ['Bar']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);
    }
}