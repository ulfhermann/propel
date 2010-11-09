<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../../../tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Test class for PHP5TableMapBuilder with schemas.
 *
 * @author     Ulf Hermann
 * @version    $Id$
 * @package    runtime.map
 */
class GeneratedRelationMapTest extends BookstoreTestBase
{
	protected $databaseMap;

	protected function setUp()
	{
		parent::setUp();
		$this->databaseMap = Propel::getDatabaseMap('bookstore-schemas');
	}

	public function testGetRightTable()
	{
		$bookTable = $this->databaseMap->getTableByPhpName('BookstoreSchemasBookstore');
		$contestTable = $this->databaseMap->getTableByPhpName('ContestBookstoreContest');
		$this->assertEquals($bookTable, $contestTable->getRelation('BookstoreSchemasBookstore')->getRightTable(), 'getRightTable() returns correct table when called on a many to one relationship');
		$this->assertEquals($contestTable, $bookTable->getRelation('ContestBookstoreContest')->getRightTable(), 'getRightTable() returns correct table when called on a one to many relationship');
		$bookCustomerTable = $this->databaseMap->getTableByPhpName('BookstoreSchemasCustomer');
		$bookCustomerAccTable = $this->databaseMap->getTableByPhpName('BookstoreSchemasCustomerAccount');
		$this->assertEquals($bookCustomerAccTable, $bookCustomerTable->getRelation('BookstoreSchemasCustomerAccount')->getRightTable(), 'getRightTable() returns correct table when called on a one to one relationship');
		$this->assertEquals($bookCustomerTable, $bookCustomerAccTable->getRelation('BookstoreSchemasCustomer')->getRightTable(), 'getRightTable() returns correct table when called on a one to one relationship');
	}

	public function testColumnMappings()
	{
		$contestTable = $this->databaseMap->getTableByPhpName('ContestBookstoreContest');
		$this->assertEquals(array('contest.bookstore_contest.BOOKSTORE_ID' => 'bookstore_schemas.bookstore.ID'), $contestTable->getRelation('BookstoreSchemasBookstore')->getColumnMappings(), 'getColumnMappings returns local to foreign by default');
		$this->assertEquals(array('contest.bookstore_contest.BOOKSTORE_ID' => 'bookstore_schemas.bookstore.ID'), $contestTable->getRelation('BookstoreSchemasBookstore')->getColumnMappings(RelationMap::LEFT_TO_RIGHT), 'getColumnMappings returns local to foreign when asked left to right for a many to one relationship');

		$bookTable = $this->databaseMap->getTableByPhpName('BookstoreSchemasBookstore');
		$this->assertEquals(array('contest.bookstore_contest.BOOKSTORE_ID' => 'bookstore_schemas.bookstore.ID'), $bookTable->getRelation('ContestBookstoreContest')->getColumnMappings(), 'getColumnMappings returns local to foreign by default');
		$this->assertEquals(array('bookstore_schemas.bookstore.ID' => 'contest.bookstore_contest.BOOKSTORE_ID'), $bookTable->getRelation('ContestBookstoreContest')->getColumnMappings(RelationMap::LEFT_TO_RIGHT), 'getColumnMappings returns foreign to local when asked left to right for a one to many relationship');

		$bookCustomerTable = $this->databaseMap->getTableByPhpName('BookstoreSchemasCustomer');
		$this->assertEquals(array('bookstore_schemas.customer_account.CUSTOMER_ID' => 'bookstore_schemas.customer.ID'), $bookCustomerTable->getRelation('BookstoreSchemasCustomerAccount')->getColumnMappings(), 'getColumnMappings returns local to foreign by default');
		$this->assertEquals(array('bookstore_schemas.customer.ID' => 'bookstore_schemas.customer_account.CUSTOMER_ID'), $bookCustomerTable->getRelation('BookstoreSchemasCustomerAccount')->getColumnMappings(RelationMap::LEFT_TO_RIGHT), 'getColumnMappings returns foreign to local when asked left to right for a one to one relationship');
	}

}
