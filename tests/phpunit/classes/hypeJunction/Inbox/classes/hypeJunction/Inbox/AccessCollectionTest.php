<?php

namespace hypeJunction\Inbox;

use PHPUnit_Framework_TestCase;
use stdClass;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-12-04 at 11:46:09.
 */
class AccessCollectionTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var AccessCollection
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$this->object = new AccessCollection;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {

	}

	/**
	 * @covers hypeJunction\Inbox\AccessCollection::create
	 */
	public function testCreate() {
		$this->assertInstanceOf(get_class($this->object), AccessCollection::create(array()));
	}

	/**
	 * @covers hypeJunction\Inbox\AccessCollection::add
	 * @covers hypeJunction\Inbox\AccessCollection::members
	 * @covers hypeJunction\Inbox\AccessCollection::group
	 */
	public function testAdd() {
		$mock = $this->getMock('\\ElggEntity');
		$mock->expects($this->once())
				->method('getGUID')
				->willReturn(4);

		$guids = $this->object
				->add('foo')
				->add(array('bar', new stdClass))
				->add(0)
				->add(1)
				->add(array(2, 3))
				->add($mock)
				->add(array(array(5, 6), array(array(7, 8, array(9)))))
				->members();

		$this->assertEquals(array(1, 2, 3, 4, 5, 6, 7, 8, 9), $guids);
	}

	/**
	 * @covers hypeJunction\Inbox\AccessCollection::add
	 * @covers hypeJunction\Inbox\AccessCollection::calcAccessHash
	 */
	public function testCalcAccessHash() {
		$this->object
				->add('foo')
				->add(array('bar', new stdClass))
				->add(array(5, 4))
				->add(1)
				->add(array(2, 3))
				->add(0);
		$this->assertEquals('e9e78cd04795bac4aa11491434d22bfa3cc8490d', $this->object->calcAccessHash());
	}

	/**
	 * @covers hypeJunction\Inbox\AccessCollection::getCollectionId
	 */
	public function testGetCollectionIdExistingCollection() {
		$mock = $this->getMockBuilder('hypeJunction\\Inbox\\AccessCollection')
				->setMethods(array('getCollectionIdByName'))
				->getMock();

		$mock->expects($this->any())
				->method('getCollectionIdByName')
				->willReturnMap(array(
					array('d4b2d74332c4368b8ef3b388292faffe6c4a16f5', 1),
					array('938f45b6f862f241c11310d5a22309483f5b99e0', 2),
		));

		$this->assertEquals(1, $mock->add(array(1, 2, 3))->getCollectionId());
		$this->assertEquals(2, $mock->add(array(4, 5, 6))->getCollectionId());
	}

	/**
	 * @covers hypeJunction\Inbox\AccessCollection::getCollectionId
	 */
	public function testGetCollectionIdNonExistingCollection() {
		$mock = $this->getMockBuilder('hypeJunction\\Inbox\\AccessCollection')
				->setMethods(array('getCollectionIdByName', 'createCollection'))
				->getMock();

		$mock->expects($this->any())
				->method('getCollectionIdByName')
				->willReturn(0);

		$mock->expects($this->any())
				->method('createCollection')
				->willReturnMap(array(
					array('d4b2d74332c4368b8ef3b388292faffe6c4a16f5', array(1, 2, 3), 1),
					array('938f45b6f862f241c11310d5a22309483f5b99e0', array(1, 2, 3, 4, 5, 6), 2),
		));

		$this->assertEquals(1, $mock->add(array(1, 2, 3))->getCollectionId());
		$this->assertEquals(2, $mock->add(array(4, 5, 6))->getCollectionId());
	}

	/**
	 * @covers hypeJunction\Inbox\AccessCollection::createCollection
	 * @todo   Implement testCreateCollection().
	 */
	public function testCreateCollection() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
				'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers hypeJunction\Inbox\AccessCollection::getCollectionIdByName
	 * @todo   Implement testGetCollectionIdByName().
	 */
	public function testGetCollectionIdByName() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
				'This test has not been implemented yet.'
		);
	}

}