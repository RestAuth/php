<?php

require_once( 'src/restauth.php' );

$conn = null;
$user1 = null;
$user2 = null;
$user4 = null;
$group1 = null;
$group2 = null;
$group3 = null;
$group4 = null;
$username1 = "mati 愐";
$username2 = "mati 愑";
$username3 = "mati a";
$username4 = "mati b";
$username4 = "mati c"; // not created by setUp
$groupname1 = "group 愒";
$groupname2 = "group 愓";
$groupname3 = "group a";
$groupname4 = "group b";
$groupname5 = "group c"; // not created by setUp
 
class MetaGroupTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		global $conn, $username1, $username2, $username3, $username4;
		global $groupname1, $groupname2, $groupname3, $groupname4;
		global $user1, $user2, $user3, $user4;
		global $group1, $group2, $group3, $group4;

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

		$user1 = RestAuthUser::create( $conn, $username1, "foobar" );
		$user2 = RestAuthUser::create( $conn, $username2, "blabla" );
		$user3 = RestAuthUser::create( $conn, $username3, "labalaba" );
		$user4 = RestAuthUser::create( $conn, $username4, "labalaba" );

		$group1 = RestAuthGroup::create( $conn, $groupname1 );
		$group2 = RestAuthGroup::create( $conn, $groupname2 );
		$group3 = RestAuthGroup::create( $conn, $groupname3 );
		$group4 = RestAuthGroup::create( $conn, $groupname4 );
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

	public function testSimpleInheritance() {
		global $conn, $group1, $group2, $group3, $group4;
		global $user1, $user2, $user3, $user4;

		// add some memberships:
		$group1->add_user( $user1 );
		$group1->add_user( $user2 );
		$group2->add_user( $user2 ); // user2 in both groups!
		$group2->add_user( $user3 );
		$group2->add_user( $user4 );

		// verify initial state
		$this->assertEquals( array( $user1, $user2 ),
			$group1->get_members() );
		$this->assertEquals( array( $user2, $user3, $user4 ),
			$group2->get_members() );

		// make group2 a subgroup of group1
		$group1->add_group( $group2 );

		// verify that group1 hasn't changed
		$this->assertEquals( array( $user1, $user2 ),
			$group1->get_members() );
		$this->assertTrue( $group1->is_member( $user1 ) );
		$this->assertTrue( $group1->is_member( $user2 ) );
		$this->assertFalse( $group1->is_member( $user3 ) );
		$this->assertFalse( $group1->is_member( $user4 ) );

		// verify that group2 now inherits memberships from group1:
		$this->assertEquals( array( $user1, $user2, $user3, $user4 ),
			$group2->get_members() );
		$this->assertTrue( $group2->is_member( $user1 ) );
		$this->assertTrue( $group2->is_member( $user2 ) );
		$this->assertTrue( $group2->is_member( $user3 ) );
		$this->assertTrue( $group2->is_member( $user4 ) );

		// verify subgroups:
		$this->assertEquals( array( $group2 ), $group1->get_groups() );
		$this->assertEquals( array(), $group2->get_groups() );
	}

	public function testAddInvalidGroup() {
		global $conn, $group1, $groupname5;

		try {
			$group1->add_group( $groupname5 );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "group", $e->get_type() );
			$this->assertEquals( array(), $group1->get_groups() );
		}
	}

	public function testAddGroupToInvalidGroup() {
		global $conn, $group1, $groupname5;
		$group5 = new RestAuthGroup( $conn, $groupname5 );
		try {
			$group5->add_group( $group1 );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "group", $e->get_type() );
			$this->assertEquals( array(), $group1->get_groups() );
		}
	}

	public function testRemoveGroup() {
		global $conn, $group1, $group2, $user1, $user2;

		$group1->add_user( $user1 );
		$group2->add_user( $user2 );
		
		// verify initial state:
		$this->assertEquals( array( $user1 ), $group1->get_members() );
		$this->assertEquals( array( $user2 ), $group2->get_members() );
		$this->assertTrue( $group1->is_member( $user1 ) );
		$this->assertTrue( $group2->is_member( $user2 ) );
		$this->assertFalse( $group1->is_member( $user2 ) );
		$this->assertFalse( $group2->is_member( $user1 ) );

		// create group-relationship
		$group1->add_group( $group2 );

		// verify state now:
		$this->assertEquals( array( $user1 ), $group1->get_members() );
		$this->assertEquals( array( $user1, $user2 ), 
			$group2->get_members() );
		$this->assertTrue( $group1->is_member( $user1 ) );
		$this->assertTrue( $group2->is_member( $user1 ) );
		$this->assertTrue( $group2->is_member( $user2 ) );
		$this->assertFalse( $group1->is_member( $user2 ) );

		$group1->remove_group( $group2 );

		// verify inital state:
		$this->assertEquals( array( $user1 ), $group1->get_members() );
		$this->assertEquals( array( $user2 ), $group2->get_members() );
		$this->assertTrue( $group1->is_member( $user1 ) );
		$this->assertTrue( $group2->is_member( $user2 ) );
		$this->assertFalse( $group1->is_member( $user2 ) );
		$this->assertFalse( $group2->is_member( $user1 ) );
	}

	public function testRemoveGroupNotMember() {
		global $conn, $group1, $group2;
		
		try {
			$group1->remove_group( $group2 );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "group", $e->get_type() );
		}
	}

	public function testRemoveInvalidGroup() {
		global $conn, $group1, $groupname5;

		try {
			$group1->remove_group( $groupname5 );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "group", $e->get_type() );
		}
	}

	public function testGetGroupsInvalidGroup() {
		global $conn, $groupname5;
		$group5 = new RestAuthGroup( $conn, $groupname5 );

		try {
			$group5->get_groups();
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "group", $e->get_type() );
		}
	}
}

?>
