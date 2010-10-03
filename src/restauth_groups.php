<?php

require_once( 'restauth_common.php' );
require_once( 'restauth_users.php' );

class RestAuthGroupNotFound extends RestAuthResourceNotFound {}
class RestAuthGroupExists extends RestAuthResourceConflict {}

function RestAuthCreateGroup( $conn, $name ) {
	$resp = $conn->post( '/groups/', array( 'group' => $name ) );
	switch ( $resp->code ) {
		case 201: return new RestAuthGroup( $conn, $name );
		case 409: throw new RestAuthGroupExists();
		default: throw new RestAuthUnknownStatus();
	}
}

function RestAuthGetGroup( $conn, $name ) {
}

function RestAuthGetAllGroups( $conn, $user=NULL, $recursive=true ) {
	$params = array();
	if ( $user )
		$params['user'] = $user;
	if ( ! $recursive )
		$params['nonrecursive'] = 1;

	$resp = $conn->get( '/groups/', $params );
	switch ( $resp->code ) {
		case 200: 
			$groups = array();
			foreach ( json_decode( $resp->body ) as $groupname ) {
				$groups[] = new RestAuthGroup( $conn, $groupname );
			}
			return $groups;
		default: throw new RestAuthUnknownStatus();
	}
}

class RestAuthGroup extends RestAuthResource {
	function __construct( $conn, $name ) {
		$this->prefix = '/groups/';
		$this->conn = $conn;
		$this->name = $name;
	}

	function get_members( $recursive = true ) {
		$params = array();
		if ( ! $recursive )
			$params['nonrecursive'] = 1;

		$resp = $this->get( $this->name, $params );
		switch ( $resp->code ) {
			case 200: 
				$users = array();
				foreach( json_decode( $resp->body ) as $username ) {
					$users[] = new RestAuthUser( $this->conn, $username );
				}
				return $users;
			case 404: throw new RestAuthGroupNotFound();
			default: throw new RestAuthUnknownStatus();
		}
	}

	function add_user( $user, $autocreate = true ) {
		$params = array( 'user' => $user->name );
		if ( $autocreate )
			$params['autocreate'] = 1;

		$resp = $this->post( $this->name, $params );
		switch ( $resp->code ) {
			case 200: return;
			case 404: switch( $resp->headers['Resource'] ) {
				case 'User':
					throw new RestAuthUserNotFound();
				case 'Group': 
					throw new RestAuthGroupNotFound();
				default: 
					throw new RestAuthException(
						"Received 404 without Resource header" );
				}
			default: throw new RestAuthUnknownStatus();
		}
	}

	function add_group( $group, $autocreate = true ) {
		$params = array( 'group' => $group->name );
		if ( $autocreate )
			$params['autocreate'] = 1;
		
		$resp = $this->post( $this->name, $params );
		switch ( $resp->code ) {
			case 200: return;
			case 404: switch( $resp->headers['Resource'] ) {
				case 'Group': 
					throw new RestAuthGroupNotFound();
				default: 
					throw new RestAuthException(
						"Received 404 without Resource header" );
				}
			default: throw new RestAuthUnknownStatus();
		}
	}

	function is_member( $user, $recursive = true ) {
		$params = array();
		if ( ! $recursive )
			$params['nonrecursive'] = 1;

		$url = $this->name . '/' . $user->name;
		$resp = $this->get( $url, $params );

		switch ( $resp->code ) {
			case 200: return true;
			case 404:
				switch( $resp->headers['Resource'] ) {
					case 'User':
						throw new RestAuthUserNotFound();
					case 'Group': 
						throw new RestAuthGroupNotFound();
					default: 
						throw new RestAuthException(
							"Received 404 without Resource header" );
				}
			default:
				throw new RestAuthUnknownStatus();
		}
	}

	function remove() {
		$resp = $this->delete( $this->name );
		switch ( $resp->code ) {
			case 200: return;
			case 404: throw new RestAuthGroupNotFound();
			default: throw new RestAuthUnknownStatus();
		}
	}

	function remove_user( $user ) {
		$url = $this->name . '/' . $user->name;
		$resp = $this->delete( $url );

		switch ( $resp->code ) {
			case 200: return true;
			case 404:
				switch( $resp->headers['Resource'] ) {
					case 'User':
						throw new RestAuthUserNotFound();
					case 'Group': 
						throw new RestAuthGroupNotFound();
					default: 
						throw new RestAuthException(
							"Received 404 without Resource header" );
				}
			default:
				throw new RestAuthUnknownStatus();
		}
	}
}

?>
