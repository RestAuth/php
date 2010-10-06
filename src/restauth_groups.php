<?php
/**
 * Code related to RestAuthGroup handling.
 *
 * @package php-restauth
 */

/**
 * imports required for the code here.
 */
require_once( 'restauth_common.php' );
require_once( 'restauth_users.php' );

/**
 * Thrown when a group is not found.
 *
 * @package php-restauth
 */
class RestAuthGroupNotFound extends RestAuthResourceNotFound {}
/**
 * Thrown when a group that is supposed to be created already exists.
 *
 * @package php-restauth
 */
class RestAuthGroupExists extends RestAuthResourceConflict {}

/**
 * Factory method that creates a new group in RestAuth.
 * 
 * @param RestAuthConnection $conn A connection to a RestAuth service.
 * @param string $name The name of the new group.
 *
 * @param string $name The name of the new group
 * @throws {@link RestAuthGroupExists} If the group already exists.
 * @throws {@link RestAuthBadRequest} When the request body could not be parsed.
 * @throws {@link RestAuthUnauthorized} When service authentication failed.
 * @throws {@link RestAuthForbidden} When service authentication failed and
 *      authorization is not possible from this host.
 * @throws {@link RestAuthInternalServerError} When the RestAuth service returns
 *      HTTP status code 500
 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
 */
function RestAuthCreateGroup( $conn, $name ) {
	$resp = $conn->post( '/groups/', array( 'group' => $name ) );
	switch ( $resp->code ) {
		case 201: return new RestAuthGroup( $conn, $name );
		case 409: throw new RestAuthGroupExists();
		default: throw new RestAuthUnknownStatus();
	}
}

/**
 * Factory method that gets an existing user from RestAuth.
 * 
 * @param RestAuthConnection $conn A connection to a RestAuth service.
 * @param string $name The name of the new group.
 *
 * @throws {@link RestAuthBadRequest} When the request body could not be parsed.
 * @throws {@link RestAuthUnauthorized} When service authentication failed.
 * @throws {@link RestAuthForbidden} When service authentication failed and
 *      authorization is not possible from this host.
 * @throws {@link RestAuthInternalServerError} When the RestAuth service returns
 *      HTTP status code 500
 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
 */
function RestAuthGetGroup( $conn, $name ) {
}

/**
 * Factory method that gets all groups for this service known to RestAuth.
 *
 * @param RestAuthConnection $conn A connection to a RestAuth service.
 * @param string $user Limit the output to groups where the user with this name
 *	is a member of.
 * @param boolean $recursive Disable recursive group parsing.
 *
 * @throws {@link RestAuthBadRequest} When the request body could not be parsed.
 * @throws {@link RestAuthUnauthorized} When service authentication failed.
 * @throws {@link RestAuthForbidden} When service authentication failed and
 *      authorization is not possible from this host.
 * @throws {@link RestAuthInternalServerError} When the RestAuth service returns
 *      HTTP status code 500
 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
 */
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

/**
 * This class acts as a frontend for actions related to groups.
 *
 * @package php-restauth
 */
class RestAuthGroup extends RestAuthResource {
	/**
	 * Constructor that initializes an object representing a group in RestAuth.
	 * 
	 * @param RestAuthConnection $conn A connection to a RestAuth service.
	 * @param string $name The name of the new group.
	 */
	function __construct( $conn, $name ) {
		$this->prefix = '/groups/';
		$this->conn = $conn;
		$this->name = $name;
	}

	/**
	 * Get all members of this group.
	 *
	 * @param boolean $recursive Set to false to disable recurive group
	 *	parsing.
	 * @return array Array of {@link RestAuthUser users}.
	 *
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 * 	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 * 	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is
	 *	unknown.
	 */
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

	/**
	 * Add a user to this group.
	 *
	 * @param RestAuthUser $user The user to add
	 * @param boolean $autocreate Set to false if you don't want to
	 *	automatically create the group if it doesn't exist.
	 *
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 *	parsed.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 * 	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 * 	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is
	 *	unknown.
	 */
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

	/**
	 * Add a group to this group.
	 *
	 * @param RestAuthGroup $group The group to add
	 * @param boolean $autocreate Set to false if you don't want to
	 *	automatically create the group if it doesn't exist.
	 *
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 *	parsed.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 * 	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 * 	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is
	 *	unknown.
	 */
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

	/**
	 * Check if the named user is a member.
	 *
	 * @param RestAuthUser $user The user in question.
	 * @param boolean $recursive Set to false to disable recurive group
	 *	parsing.
	 * @return boolean true if the user is a member, false if not
	 *
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 * 	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 * 	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is
	 *	unknown.
	 */
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

	/**
	 * Delete this group.
	 *
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 * 	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 * 	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is
	 *	unknown.
	 */
	function remove() {
		$resp = $this->delete( $this->name );
		switch ( $resp->code ) {
			case 200: return;
			case 404: throw new RestAuthGroupNotFound();
			default: throw new RestAuthUnknownStatus();
		}
	}

	/**
	 * Remove the given user from the group.
	 *
	 * @param RestAuthUser $user The user to remove
	 *
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 * 	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 * 	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is
	 *	unknown.
	 */
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
