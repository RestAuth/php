<?php

require_once( 'PHPUnit/Framework.php' );
require_once( 'src/restauth.php' );

$user = null;
$conn = null;
$username = "mati 愐";
$password = "pass 愑";
$propKey = "key 愒";
$propVal = "val 愓";
 
class PropertyTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		global $username, $user, $password, $conn;

		$host = 'http://[::1]:8000';
		$user = 'vowi';
		$pass = 'vowi';

		$conn = new RestAuthConnection( $host, $user, $pass );

		$users = RestAuthUser::get_all( $conn );
		if ( count( $users ) ) {
			throw new Exception( "Found " . count( $users ) . " left over users." );
		}

		$user = RestAuthUser::create( $conn, $username, $password );
	}
	public function tearDown() {
		global $conn;

		$users = RestAuthUser::get_all( $conn );
		foreach ( $users as $user ) {
			$user->remove();
		}
	}

	public function testCreateProperty() {
		global $conn, $user, $propKey, $propVal;

		$user->create_property( $propKey, $propVal );
		$this->assertEquals( array( $propKey => $propVal ),
			$user->get_properties() );
		$this->assertEquals( $propVal, 
			$user->get_property( $propKey ) );
	}

	public function testCreatePropertyTwice() {
		global $conn, $user, $propKey, $propVal;

		$user->create_property( $propKey, $propVal );
		try {
			$user->create_property( $propKey, $propVal . " new" );
			$this->fail();
		} catch ( RestAuthPropertyExists $e ) {
			$this->assertEquals( array( $propKey => $propVal ),
				$user->get_properties() );
			$this->assertEquals( $propVal, 
				$user->get_property( $propKey ) );
		}
	}

	public function testCreatePropertyWithInvalidUser() {
		global $conn, $user, $propKey, $propVal;
		$username = "invalid name";

		$invalidUser = new RestAuthUser( $conn, $username );
		try {
			$invalidUser->create_property( $propKey, $propVal );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );
			$this->assertEquals( array( $user ), 
				RestAuthUser::get_all($conn) );
		}
	}

	public function testSetProperty() {
		global $conn, $user, $propKey, $propVal;

		$this->assertNull( $user->set_property( $propKey, $propVal ) );
		$this->assertEquals( array( $propKey => $propVal ),
			$user->get_properties() );
		$this->assertEquals( $propVal, 
			$user->get_property( $propKey ) );
	}

	public function testSetPropertyTwice() {
		global $conn, $user, $propKey, $propVal;
		$newVal = "foobar";

		$this->assertNull( $user->set_property( $propKey, $propVal ) );
		$this->assertEquals( array( $propKey => $propVal ),
			$user->get_properties() );
		$this->assertEquals( $propVal, 
			$user->get_property( $propKey ) );

		$this->assertEquals( $propVal, 
			$user->set_property( $propKey, $newVal ) );
		$this->assertEquals( array( $propKey => $newVal ),
			$user->get_properties() );
		$this->assertEquals( $newVal, 
			$user->get_property( $propKey ) );
	}

	public function testSetPropertyWithInvalidUser() {
		global $conn, $user, $propKey, $propVal;
		$username = "invalid name";

		$invalidUser = new RestAuthUser( $conn, $username );
		try {
			$invalidUser->set_property( $propKey, $propVal );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );
			$this->assertEquals( array( $user ), 
				RestAuthUser::get_all( $conn ) );
		}
	}

	public function testRemoveProperty() {
		global $conn, $user, $propKey, $propVal;
		
		$this->assertNull( $user->set_property( $propKey, $propVal ) );
		$this->assertEquals( array( $propKey => $propVal ),
			$user->get_properties() );
		$this->assertEquals( $propVal, 
			$user->get_property( $propKey ) );

		$user->remove_property( $propKey );
		$this->assertEquals( array(), $user->get_properties() );
	}

	public function testRemoveInvalidProperty() {
		global $conn, $user, $propKey, $propVal;
		$user->create_property( $propKey, $propVal );

		$wrongKey = $propKey . " foo";

		try {
			$user->remove_property( $wrongKey );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "property", $e->get_type() );
			$this->assertEquals( array( $propKey => $propVal ),
				$user->get_properties() );
			$this->assertEquals( $propVal, 
				$user->get_property( $propKey ) );
		}
	}

	public function testRemovePropertyWithInvalidUser() {
		global $conn, $user, $propKey, $propVal;
		$user->set_property( $propKey, $propVal );
		$username = "invalid name";

		$invalidUser = new RestAuthUser( $conn, $username );
		try {
			$invalidUser->remove_property( $propKey );
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );

			$this->assertEquals( array( $propKey => $propVal ),
				$user->get_properties() );
			$this->assertEquals( $propVal, 
				$user->get_property( $propKey ) );
		}
	}

	public function testGetInvalidProperty() {
		global $conn, $user, $propKey, $propVal;

		try {
			$user->get_property( $propKey ); 
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "property", $e->get_type() );
		}
	}

	public function testGetPropertyInvalidUser() {
		global $conn, $user, $propKey, $propVal;
		$username = "invalid name";

		$invalidUser = new RestAuthUser( $conn, $username );
		try {
			$invalidUser->get_property( $propKey ); 
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );
		}
	}

	public function testGetPropertiesInvalidUser() {
		global $conn, $user, $propKey, $propVal;
		$username = "invalid name";

		$invalidUser = new RestAuthUser( $conn, $username );
		try {
			$invalidUser->get_properties( $propKey ); 
			$this->fail();
		} catch ( RestAuthResourceNotFound $e ) {
			$this->assertEquals( "user", $e->get_type() );
		}
	}
}

?>
