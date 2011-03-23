<?php

require_once( 'PHPUnit/Framework.php' );
require_once( 'src/restauth.php' );

$username = "mati 愐";
$password = "pass 愑";
$propKey = "key 愒";
$propVal = "val 愓";

class UserTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		$host = 'http://[::1]:8000';
		$user = 'vowi';
		$pass = 'vowi';

		$this->conn = new RestAuthConnection( $host, $user, $pass );

		$users = RestAuthUser::get_all( $this->conn );
		if ( count( $users ) ) {
			throw new Exception( "Found " . count( $users ) . " left over users." );
		}
	}
	public function tearDown() {
		$users = RestAuthUser::get_all( $this->conn );
		foreach ( $users as $user ) {
			$user->remove();
		}
	}

	public function testCreateUser() {
		global $username, $password;

		$user = RestAuthUser::create( $this->conn, $username, $password );

		$this->assertEquals( 
			array( $user ), RestAuthUser::get_all( $this->conn ) );
		$this->assertEquals( 
			$user, RestAuthUser::get( $this->conn, $username ) );
	}

	public function testCreateInvalidUser() {
		global $username, $password;

		try {
			RestAuthUser::create( $this->conn, "foo/bar", "don't care" );
			$this->fail();
		} catch ( RestAuthPreconditionFailed $e ) {
			$this->assertEquals( array(), RestAuthUser::get_all( $this->conn ) );
		}
	}
	public function testCreateUserTwice() {
		global $username, $password;
		$new_pass = "new " . $password;

		$user = RestAuthUser::create( $this->conn, $username, $password );
		$this->assertEquals( 
			$user, RestAuthUser::get( $this->conn, $username ) );

		try {
			RestAuthUser::create( $this->conn, $username, $new_pass );
			$this->fail();
		} catch ( RestAuthUserExists $e ) {
			$this->assertTrue( $user->verify_password( $password ) );
			$this->assertFalse( $user->verify_password( $new_pass ) );
		}
	}
	public function testVerifyPassword() {
		global $username, $password;
		$user = RestAuthUser::create( $this->conn, $username, $password );

		$this->assertTrue( $user->verify_password( $password ) );
		$this->assertFalse( $user->verify_password( "something else" ) );
	}

	public function testVerifyPasswordInvalidUser() {
		global $username, $password;
		
		$user = new RestAuthUser( $this->conn, $username );

		$this->assertFalse( $user->verify_password( "foobar" ) );
	}

	public function testSetPassword() {
		global $username, $password;
		$new_pass = "something else";


		$user = RestAuthUser::create( $this->conn, $username, $password );
		$this->assertTrue( $user->verify_password( $password ) );
		$this->assertFalse( $user->verify_password( $new_pass ) );

		$user->set_password( $new_pass );

		$this->assertFalse( $user->verify_password( $password ) );
		$this->assertTrue( $user->verify_password( $new_pass ) );
	}

	public function testSetPasswordInvalidUser() {
		global $username, $password;
		
		$user = new RestAuthUser( $this->conn, $username );
		try {
			$user->set_password( $password );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );
			$this->assertEquals( array(), RestAuthUser::get_all( $this->conn ) );
		}
	}

	public function testSetTooShortPasswort() {
		global $username, $password;
		
		$user = RestAuthUser::create( $this->conn, $username, $password );
		try {
			$user->set_password( "x" );
			$this->fail();
		} catch ( RestAuthPreconditionFailed $e ) {
			$this->assertFalse( $user->verify_password( "x" ) );
			$this->assertTrue( $user->verify_password( $password ) );
		}
	}

	public function testGetInvalidUser() {
		global $username, $password;

		try {
			RestAuthUser::get( $this->conn, $username );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );
		}
	}

	public function testRemoveUser() {
		global $username, $password;
		$user = RestAuthUser::create( $this->conn, $username, $password );
	
		$user->remove();
		$this->assertEquals( array(), RestAuthUser::get_all( $this->conn ) );
		try {
			RestAuthUser::get( $this->conn, $username );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );
		}
	}

	public function testRemoveInvalidUser() {
		global $username, $password;
		
		$user = new RestAuthUser( $this->conn, $username );
		try {
			$user->remove();
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );
		}

	}
}

?>
