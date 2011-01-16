<?php

require_once( 'src/restauth.php' );

$conn = null;
$user = null;
$group = null;
$username = "mati 愐";
$username2 = "mati 愑";
$groupname = "group 愒";
$groupname2 = "group 愓";
$groupname3 = "group a";
 
class SimpleUserGroupTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		global $conn, $user, $username, $group, $groupname;

		$host = 'http://localhost:8000';
		$user = 'vowi';
		$pass = 'vowi';
		$conn = new RestAuthConnection( $host, $user, $pass );

		$users = RestAuthUser::get_all( $conn );
		if ( count( $users ) ) {
			throw new Exception( "Found " . count( $users ) . " left over users." );
		}
		$groups = RestAuthGroup::get_all( $conn );
		if ( count( $groups ) ) {
			throw new Exception( "Found " . count( $groups ) . " left over users." );
		}

		$user = RestAuthUser::create( $conn, $username, "foobar" );
		$group = RestAuthGroup::create( $conn, $groupname );
	}

	public function tearDown() {
		global $conn;

		$users = RestAuthUser::get_all( $conn );
		foreach ( $users as $user ) {
			$user->remove();
		}
		$groups = RestAuthGroup::get_all( $conn );
		foreach ( $groups as $group ) {
			$group->remove();
		}
	}

	public function testAddGroup() {
		global $user, $group, $groupname;
		$user->add_group( $groupname );
		$this->assertEquals( array($group), $user->get_groups() );
		$this->assertTrue( $user->in_group( $groupname ) );
	}

	public function testInGroup() {
		global $user, $group, $groupname;
		$this->assertFalse( $user->in_group( $groupname ) );
		$user->add_group( $groupname );
		$this->assertEquals( array($group), $user->get_groups() );
		$this->assertTrue( $user->in_group( $groupname ) );
	}

	public function testRemoveGroup() {
		global $user, $group, $groupname;

		$this->assertFalse( $user->in_group( $groupname ) );
		$this->assertEquals( array(), $user->get_groups() );

		$user->add_group( $groupname );
		$this->assertTrue( $user->in_group( $groupname ) );
		$this->assertEquals( array($group), $user->get_groups() );

		$user->remove_group( $groupname );
		$this->assertFalse( $user->in_group( $groupname ) );
		$this->assertEquals( array(), $user->get_groups() );
	}

	public function testGetGroupsInvalidUser() {
		global $conn;
		$user = new RestAuthUser( $conn, "foobar" );
		try {
			$user->get_groups();
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );
		}
	}
}

?>
