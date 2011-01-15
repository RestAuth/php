<?php

require_once( 'PHPUnit/Framework.php' );
require_once( 'src/restauth.php' );

$user1 = null;
$conn = null;
$username1 = "mati 愐";
$username2 = "mati 愑";
$username3 = "boring";
$groupname1 = "group 愒";
$groupname2 = "group 愓";
 
class GroupTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		global $conn, $username1, $username2, $groupname1, $groupname2;
		global $user1, $group1, $group2;

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

	public function testCreateGroup() {
		global $conn, $group1, $groupname1;

		$group1 = RestAuthGroup::create( $conn, $groupname1 );
		$this->assertEquals( array( $group1 ), 
			RestAuthGroup::get_all( $conn ) );
		$this->assertEquals( $group1, 
			RestauthGroup::get( $conn, $groupname1 ) );
	}

	public function testCreateGroupTwice() {
		global $conn, $group1, $groupname1;
		
		$group1 = RestAuthGroup::create( $conn, $groupname1 );
		try {
			RestAuthGroup::create( $conn, $groupname1 );
			$this->fail();
		} catch ( RestAuthGroupExists $e ) {
			$this->assertEquals( array( $group1 ), 
				RestAuthGroup::get_all( $conn ) );
		}	
	}

	public function testCreateInvalidGroup() {
		global $conn;

		try {
			RestAuthGroup::create( $conn, "foo/bar" );
			$this->fail();
		} catch ( RestAuthPreconditionFailed $e ) {
			$this->assertEquals( array( ), 
				RestAuthGroup::get_all( $conn ) );
		}
	}

	public function testAddUser() {
		global $conn, $user1, $username2, $username3, $groupname1;
		global $groupname2;
		$group1 = RestAuthGroup::create( $conn, $groupname1 );
		$group2 = RestAuthGroup::create( $conn, $groupname2 );
		$user2 = RestAuthUser::create( $conn, $username2, "foobar" );
		$user3 = RestAuthUser::create( $conn, $username3, "foobar" );

		$this->assertEquals( array( $group1, $group2 ),
			RestAuthGroup::get_all( $conn ) );

		$group1->add_user( $user1 );
		$group2->add_user( $user2 );
		$group2->add_user( $user3 );

		$this->assertEquals( array( $user1 ), $group1->get_members() );
		$this->assertEquals( array( $user2, $user3 ), 
			$group2->get_members() );

		$this->assertTrue( $group1->is_member( $user1 ) );
		$this->assertTrue( $group2->is_member( $user2 ) );
		$this->assertTrue( $group2->is_member( $user3 ) );
		$this->assertFalse( $group2->is_member( $user1 ) );
		$this->assertFalse( $group1->is_member( $user2 ) );
		$this->assertFalse( $group1->is_member( $user3 ) );
	}

	public function testAddInvalidUser() {
		global $conn, $username3, $groupname1;
		$group = RestAuthGroup::create( $conn, $groupname1 );
		$user = new RestAuthUser( $conn, $username3 );

		try {
			$group->add_user( $user );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );
			$this->assertEquals( array(), $group->get_members() );
		}
	}

	public function testAddUserToInvalidGroup() {
		global $conn, $user1, $groupname1;
		$group = new RestAuthGroup( $conn, $groupname1 );

		try {
			$group->add_user( $user1 );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "group", $e->get_type() );
			$this->assertEquals( array(), 
				RestAuthGroup::get_all( $conn ) );
		}
	}

	public function testIsMemberInvalidUser() {
		global $conn, $username3, $groupname1;
		$group = RestAuthGroup::create( $conn, $groupname1 );
		$user = new RestAuthUser( $conn, $username3 );

		$this->assertFalse( $group->is_member( $user ) );
	}

	public function testIsMemberInvalidGroup() {
		global $conn, $user1, $groupname1;

		$group = new RestAuthGroup( $conn, $groupname1 );
		try {
			$group->is_member( $user1 );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "group", $e->get_type() );
		}
	}

	public function testRemoveUser() {
		global $conn, $user1, $groupname1;
		$group = RestAuthGroup::create( $conn, $groupname1 );
		$group->add_user( $user1 );
		$this->assertEquals( array( $user1 ), $group->get_members() );

		$group->remove_user( $user1 );
		$this->assertEquals( array(), $group->get_members() );
		$this->assertFalse( $group->is_member( $user1 ) );
	}

	public function testRemoveUserNotMember() {
		global $conn, $user1, $groupname1;
		$group = RestAuthGroup::create( $conn, $groupname1 );
		$this->assertFalse( $group->is_member( $user1 ) );

		try {
			$group->remove_user( $user1 );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );
		}
	}

	public function testRemoveInvalidUser() {
		global $conn, $username3, $groupname1;
		$group = RestAuthGroup::create( $conn, $groupname1 );
		$user = new RestAuthUser( $conn, $username3 );
		
		try {
			$group->remove_user( $user );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );
		}
	}

	public function testRemoveUserFromInvalidGroup() {
		global $conn, $user1, $groupname1;
		$group = new RestAuthGroup( $conn, $groupname1 );
		
		try {
			$group->remove_user( $user1 );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "group", $e->get_type() );
		}
	}

	public function testRemoveInvalidUserFromInvalidGroup() {
		global $conn, $username3, $groupname1;
		$group = new RestAuthGroup( $conn, $groupname1 );
		$user = new RestAuthUser( $conn, $username3 );
		
		try {
			$group->remove_user( $user );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "group", $e->get_type() );
		}
	}

	public function testRemoveGroup() {
		global $conn, $groupname1;
		$group = RestAuthGroup::create( $conn, $groupname1 );
		$group->remove();
		$this->assertEquals( array(), RestAuthGroup::get_all( $conn ) );
	}

	public function testRemoveInvalidGroup() {
		global $conn, $groupname1;
		
		$group = new RestAuthGroup( $conn, $groupname1 );
		try {
			$group->remove();
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "group", $e->get_type() );
		}
	}

	public function testGetInvalidGroup() {
		global $conn, $groupname1;

		try {
			RestAuthGroup::get( $conn, $groupname1 );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "group", $e->get_type() );
		}
	}

	public function testGetMembersInvalidGroup() {
		global $conn, $groupname1;

		$group = new RestAuthGroup( $conn, $groupname1 );
		try {
			$group->get_members();
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "group", $e->get_type() );
		}
	}
}


?>
