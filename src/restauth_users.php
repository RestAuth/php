<?php

require_once( 'restauth_errors.php' );

class RestAuthUserNotFound extends RestAuthResourceNotFound {
}
class RestAuthPropertyNotFound extends RestAuthResourceNotFound {
}

class RestAuthUserExists extends RestAuthResourceConflict {
}

class RestAuthPropertyExists extends RestAuthResourceConflict {
}

function RestAuthCreateUser( $conn, $name, $password ) {
	$params = array( 'user' => $name, 'password' => $password );
	$resp = $conn->post( '/users/', $params );
	switch ( $resp->code ) {
		case 201: return new RestAuthUser( $conn, $name );
		case 409: throw new RestAuthUserExists();
		default:  throw new RestAuthUnknownStatus();
	}
}

function RestAuthGetUser( $conn, $name ) {
	$resp = $conn->get( '/users/' . $name . '/' );

	switch ( $resp->code ) {
		case 200: return new RestAuthUser( $conn, $name );
		case 404: throw new RestAuthUserNotFound();
		default: throw new RestAuthUnknownStatus();
	}
}

function RestAuthGetAllUsers( $conn ) {
	$resp = $conn->get( '/users/' );

	switch ( $resp->code ) {
		case 200:
			$response = array();
			foreach( json_decode( $resp->body ) as $name ) {
				$response[] = new RestAuthUser( $conn, $name );
			}
			return $response;
		default: throw new RestAuthUnknownStatus();
	}
}

class RestAuthUser extends RestAuthResource {
	function __construct( $conn, $name ) {
		$this->prefix = '/users/';
		$this->conn = $conn;
		$this->name = $name;
	}

	function set_password( $password ) {
		$resp = $this->put( $this->name, array( 'password' => $password ) );

		switch ( $resp->code ) {
			case 200: return;
			case 404: throw new RestAuthUserNotFound();
			default: throw new RestAuthUnknownStatus();
		}
	}

	function verify_password( $password ) {
		$resp = $this->post( $this->name, array( 'password' => $password ) );
		switch ( $resp->code ) {
			case 200: return true;
			case 404: return false;
			default: throw new RestAuthUnknownStatus();
		}
	}

	function remove() {
		$resp = $this->delete( $this->name );
		switch ( $resp->code ) {
			case 200: return;
			case 404: throw new RestAuthUserNotFound();
			default: throw new RestAuthUnknownStatus();
		}
	}

	function get_properties() {
		$resp = $this->get( $this->name, array(), array(), '/userprops/' );
		
		switch ( $resp->code ) {
			case 200:
				$props = (array) json_decode( $resp->body );
				return $props;
			case 404: throw new RestAuthUserNotFound();
			default: throw new RestAuthUnknownStatus();
		}
	}

	function create_property( $name, $value ) {
		$params = array( 'prop' => $name, 'value' =>$value );
		$resp = $this->post( $this->name, $params, array(), '/userprops/' );
		switch ( $resp->code ) {
			case 200: return;
			case 404: throw new RestAuthUserNotFound();
			case 409: throw new RestAuthPropertyExists();
			default: throw new RestAuthUnknownStatus();
		}
	}

	function set_property( $name, $value ) {
		$url = $this->name . '/' . $name;
		$params = array( 'value' => $value );
		$resp = $this->put( $url, $params, array(), '/userprops/' );
		switch ( $resp->code ) {
			case 200: return;
			case 404: throw new RestAuthUserNotFound();
			default: throw new RestAuthUnknownStatus();
		}
	}

	function get_property( $name ) {
		$url = $this->name . '/' . $name;
		$resp = $this->get( $url, array(), array(), '/userprops/' );

		switch ( $resp->code ) {
			case 200:
				return $resp->body;
			case 404:
				switch( $resp->headers['Resource'] ) {
					case 'User':
						throw new RestAuthUserNotFound();
					case 'Property':
						throw new RestAuthPropertyNotFound();
				}
				throw new RestAuthException(
					"Received 404 without Resource header" );
			default: throw new RestAuthUnknownStatus();
		}
	}

	function del_property( $name ) {
		$url = $this->name . '/' . $name;
		$resp = $this->delete( $url, array(), '/userprops/' );

		switch ( $resp->code ) {
			case 200: return;
			case 404: throw new RestAuthUserNotFound();
			default: throw new RestAuthUnknownStatus();
		}
	}
}
?>
