<?php

/*
 *	$Id: TableTest.php 1891 2010-08-09 15:03:18Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../../generator/lib/model/diff/PropelForeignKeyComparator.php';

/**
 * Tests for the PropelColumnComparator service class.
 *
 * @package    generator.model.diff
 */
class PropelForeignComparatorTest extends PHPUnit_Framework_TestCase
{
	public function testCompareNoDifference()
	{
		$c1 = new Column('Foo');
		$c2 = new Column('Bar');
		$fk1 = new ForeignKey();
		$fk1->addReference($c1, $c2);
		$t1 = new Table('Baz');
		$t1->addForeignKey($fk1);
		$c3 = new Column('Foo');
		$c4 = new Column('Bar');
		$fk2 = new ForeignKey();
		$fk2->addReference($c3, $c4);
		$t2 = new Table('Baz');
		$t2->addForeignKey($fk2);
		$this->assertFalse(PropelForeignKeyComparator::computeDiff($fk1, $fk2));
	}

	public function testCompareLocalColumn()
	{
		$c1 = new Column('Foo');
		$c2 = new Column('Bar');
		$fk1 = new ForeignKey();
		$fk1->addReference($c1, $c2);
		$t1 = new Table('Baz');
		$t1->addForeignKey($fk1);
		$c3 = new Column('Foo2');
		$c4 = new Column('Bar');
		$fk2 = new ForeignKey();
		$fk2->addReference($c3, $c4);
		$t2 = new Table('Baz');
		$t2->addForeignKey($fk2);
		$this->assertTrue(PropelForeignKeyComparator::computeDiff($fk1, $fk2));
	}

	public function testCompareForeignColumn()
	{
		$c1 = new Column('Foo');
		$c2 = new Column('Bar');
		$fk1 = new ForeignKey();
		$fk1->addReference($c1, $c2);
		$t1 = new Table('Baz');
		$t1->addForeignKey($fk1);
		$c3 = new Column('Foo');
		$c4 = new Column('Bar2');
		$fk2 = new ForeignKey();
		$fk2->addReference($c3, $c4);
		$t2 = new Table('Baz');
		$t2->addForeignKey($fk2);
		$this->assertTrue(PropelForeignKeyComparator::computeDiff($fk1, $fk2));
	}

	public function testCompareColumnMappings()
	{
		$c1 = new Column('Foo');
		$c2 = new Column('Bar');
		$fk1 = new ForeignKey();
		$fk1->addReference($c1, $c2);
		$t1 = new Table('Baz');
		$t1->addForeignKey($fk1);
		$c3 = new Column('Foo');
		$c4 = new Column('Bar');
		$c5 = new Column('Foo2');
		$c6 = new Column('Bar2');
		$fk2 = new ForeignKey();
		$fk2->addReference($c3, $c4);
		$fk2->addReference($c5, $c6);
		$t2 = new Table('Baz');
		$t2->addForeignKey($fk2);
		$this->assertTrue(PropelForeignKeyComparator::computeDiff($fk1, $fk2));
	}

	public function testCompareOnUpdate()
	{
		$c1 = new Column('Foo');
		$c2 = new Column('Bar');
		$fk1 = new ForeignKey();
		$fk1->addReference($c1, $c2);
		$fk1->setOnUpdate(ForeignKey::SETNULL);
		$t1 = new Table('Baz');
		$t1->addForeignKey($fk1);
		$c3 = new Column('Foo');
		$c4 = new Column('Bar');
		$fk2 = new ForeignKey();
		$fk2->addReference($c3, $c4);
		$fk2->setOnUpdate(ForeignKey::RESTRICT);
		$t2 = new Table('Baz');
		$t2->addForeignKey($fk2);
		$this->assertTrue(PropelForeignKeyComparator::computeDiff($fk1, $fk2));
	}

	public function testCompareOnDelete()
	{
		$c1 = new Column('Foo');
		$c2 = new Column('Bar');
		$fk1 = new ForeignKey();
		$fk1->addReference($c1, $c2);
		$fk1->setOnDelete(ForeignKey::SETNULL);
		$t1 = new Table('Baz');
		$t1->addForeignKey($fk1);
		$c3 = new Column('Foo');
		$c4 = new Column('Bar');
		$fk2 = new ForeignKey();
		$fk2->addReference($c3, $c4);
		$fk2->setOnDelete(ForeignKey::RESTRICT);
		$t2 = new Table('Baz');
		$t2->addForeignKey($fk2);
		$this->assertTrue(PropelForeignKeyComparator::computeDiff($fk1, $fk2));
	}
}
