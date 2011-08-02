<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class SQLFilterTest extends SapphireTest {
	
	function testConstruct() {
		$obj = new SQLFilter("test = %s AND test2 = %s", 1, 2);
		$this->assertEquals("test = '1' AND test2 = '2'", $obj->__toString());
	}
	
	function testPush() {
		$obj = new SQLFilter("test = %s", 1);
		$obj->push('test2 = %s', 2);
		$this->assertEquals("test = '1' AND test2 = '2'", $obj->__toString());
	}
	
	function testPushOr() {
		$obj = new SQLFilter("test = %s", 1);
		$obj->pushOr('test2 = %s', 2);
		$this->assertEquals("test = '1' OR test2 = '2'", $obj->__toString());
	}
	
	function testNoArgumentConstructor() {
		$obj = new SQLFilter();
		$obj->pushOr('test1 = %s', 'asdf');
		$obj->pushOr('test2 = %s', 2);
		$this->assertEquals("test1 = 'asdf' OR test2 = '2'", $obj->__toString());
	}
	
	function testExists() {
		$obj = new SQLFilter();
		$obj->pushOr('test1 = %s', 'asdf');
		$this->assertEquals(true, $obj->exists());
	}
	
}