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
 * Thrown when a group that is supposed to be created already exists.
 *
 * @package php-restauth
 */
class RestAuthGroupExists extends RestAuthResourceConflict {}

/**
 * This class acts as a frontend for actions related to groups.
 *
 * @package php-restauth
 */
class RestAuthGroup extends RestAuthResource {
	const prefix = '/groups/';

	/**
	 * Factory method that creates a new group in RestAuth.
	 * 
	 * @param RestAuthConnection $conn A connection to a RestAuth service.
	 * @param string $name The name of the new group.
	 *
	 * @param string $name The name of the new group
	 * @throws {@link RestAuthGroupExists} If the group already exists.
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 *	parsed.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *	and authorization is not possible from this host.
	 * @throws {@link RestAuthPreconditionFailed} When username or password is
	 *	invalid.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is
	 *	unknown.
	 */
	public static function create( $conn, $name ) {
		$resp = $conn->post( '/groups/', array( 'group' => $name ) );
		switch ( $resp->getResponseCode() ) {
			case 201: return new RestAuthGroup( $conn, $name );
			case 409: throw new RestAuthGroupExists( $resp );
			case 412: throw new RestAuthPreconditionFailed( $resp );
			// @codeCoverageIgnoreStart
			default: throw new RestAuthUnknownStatus( $resp );
		}
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Factory method that gets an existing user from RestAuth.
	 * 
	 * @param RestAuthConnection $conn A connection to a RestAuth service.
	 * @param string $name The name of the new group.
	 *
	 * @throws {@link RestAuthBadRequest} When the request body could not
	 *	be parsed.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is
	 *	unknown.
	 */
	public static function get( $conn, $name ) {
		$resp = $conn->get( '/groups/' . $name . '/' );
		switch ( $resp->getResponseCode() ) {
			case 204: return new RestAuthGroup( $conn, $name );
			case 404: throw new RestAuthResourceNotFound( $resp );
			// @codeCoverageIgnoreStart
			default: throw new RestAuthUnknownStatus( $resp );
		}
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Factory method that gets all groups for this service known to 
	 * RestAuth.
	 *
	 * @param RestAuthConnection $conn A connection to a RestAuth service.
	 * @param string $user Limit the output to groups where the user with 
	 *	this name is a member of.
	 *
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 *	parsed.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is
	 *	unknown.
	 */
	public static function get_all( $conn, $user=NULL ) {
		$params = array();
		if ( $user )
			$params['user'] = $user;
	
		$resp = $conn->get( '/groups/', $params );
		switch ( $resp->getResponseCode() ) {
			case 200: 
				$groups = array();
				foreach ( json_decode( $resp->getBody() ) as $groupname ) {
					$groups[] = new RestAuthGroup( $conn, $groupname );
				}
				return $groups;
			// @codeCoverageIgnoreStart
			default: throw new RestAuthUnknownStatus( $resp );
		}
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Constructor that initializes an object representing a group in RestAuth.
	 * 
	 * @param RestAuthConnection $conn A connection to a RestAuth service.
	 * @param string $name The name of the new group.
	 */
	public function __construct( $conn, $name ) {
		$this->prefix = '/groups/';
		$this->conn = $conn;
		$this->name = $name;
	}

	/**
	 * Get all members of this group.
	 *
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
	public function get_members() {
		$params = array();

		$resp = $this->_get( $this->name . '/users/', $params );
		switch ( $resp->getResponseCode() ) {
			case 200: 
				$users = array();
				foreach( json_decode( $resp->getBody() ) as $username ) {
					$users[] = new RestAuthUser( $this->conn, $username );
				}
				return $users;
			case 404: throw new RestAuthResourceNotFound( $resp );
			// @codeCoverageIgnoreStart
			default: throw new RestAuthUnknownStatus( $resp );
		}
		// @codeCoverageIgnoreEnd
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
	public function add_user( $user ) {
		$params = array( 'user' => $user->name );

		$resp = $this->_post( $this->name . '/users/', $params );
		switch ( $resp->getResponseCode() ) {
			case 204: return;
			case 404: throw new RestAuthResourceNotFound( $resp );
			// @codeCoverageIgnoreStart
			default: throw new RestAuthUnknownStatus( $resp );
		}
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Check if the named user is a member.
	 *
	 * @param RestAuthUser $user The user in question.
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
	public function is_member( $user ) {
		$params = array();

		$url = $this->name . '/users/' . $user->name;
		$resp = $this->_get( $url, $params );

		switch ( $resp->getResponseCode() ) {
			case 204: return true;
			case 404:
				switch ( $resp->getHeader( 'Resource-Type' ) ) {
					case 'user':
						return false;
					default: 
						throw new RestAuthResourceNotFound( $resp );
				}
			// @codeCoverageIgnoreStart
			default:
				throw new RestAuthUnknownStatus( $resp );
		}
		// @codeCoverageIgnoreEnd
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
	public function remove() {
		$resp = $this->_delete( $this->name );
		switch ( $resp->getResponseCode() ) {
			case 204: return;
			case 404: throw new RestAuthResourceNotFound( $resp );
			// @codeCoverageIgnoreStart
			default: throw new RestAuthUnknownStatus( $resp );
		}
		// @codeCoverageIgnoreEnd
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
	public function remove_user( $user ) {
		$url = $this->name . '/users/' . $user->name;
		$resp = $this->_delete( $url );

		switch ( $resp->getResponseCode() ) {
			case 204: return;
			case 404: throw new RestAuthResourceNotFound( $resp );
			// @codeCoverageIgnoreStart
			default:
				throw new RestAuthUnknownStatus( $resp );
		}
		// @codeCoverageIgnoreEnd
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
	public function add_group( $group ) {
		$params = array( 'group' => $group->name );
		
		$resp = $this->_post( $this->name . '/groups/', $params );
		switch ( $resp->getResponseCode() ) {
			case 204: return;
			case 404: throw new RestAuthResourceNotFound( $resp );
			// @codeCoverageIgnoreStart
			default: throw new RestAuthUnknownStatus( $resp );
		}
		// @codeCoverageIgnoreEnd
	}

	public function get_groups() {
		$resp = $this->_get( $this->name . '/groups/' );
		switch ( $resp->getResponseCode() ) {
			case 200: 
				$users = array();
				foreach( json_decode( $resp->getBody() ) as $username ) {
					$users[] = new RestAuthGroup( $this->conn, $username );
				}
				return $users;
			case 404: throw new RestAuthResourceNotFound( $resp );
			// @codeCoverageIgnoreStart
			default: throw new RestAuthUnknownStatus( $resp );
		}
		// @codeCoverageIgnoreEnd
	}

	public function remove_group( $group ) {
		$resp = $this->_get( $this->name . '/groups/' . $group->name . '/' );
		switch ( $resp->getResponseCode() ) {
			case 204: return;
			case 404: throw new RestAuthResourceNotFound( $resp );
			// @codeCoverageIgnoreStart
			default: throw new RestAuthUnknownStatus( $resp );
		}
		// @codeCoverageIgnoreEnd
	}
}

?>
