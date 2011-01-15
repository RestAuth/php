<?php
/**
 * @package php-restauth
 */

/**
 * General includes
 */
require_once( 'restauth_errors.php' );
require_once( 'restauth_common.php' );

/**
 * Thrown when a user is supposed to be created but already exists.
 * 
 * @package php-restauth
 */
class RestAuthUserExists extends RestAuthResourceConflict {
}

/**
 * Thrown when a property is supposed to be created but already exists.
 * 
 * @package php-restauth
 */
class RestAuthPropertyExists extends RestAuthResourceConflict {
}



/**
 * This class acts as a frontend for actions related to users.
 *
 * @package php-restauth
 */
class RestAuthUser extends RestAuthResource {
	const prefix = '/users/';

	/**
	 * Factory method that creates a new user in the RestAuth database and
	 * throws {@link RestAuthUserExists} if the user already exists.
	 *
	 * @param RestAuthConnection $conn The connection to a RestAuth service.
	 * @param string $name The name of this user.
	 * @param string $password The password for the new user
	 * @throws {@link RestAuthUserExists} If the user already exists.
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 * 	parsed.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 * 	and authorization is not possible from this host.
	 * @throws {@link RestAuthPreconditionFailed} When username or password is
	 *	invalid.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is
	 *	unknown.
	 */
	public static function create( $conn, $name, $password ) {
		$params = array( 'user' => $name, 'password' => $password );
		$resp = $conn->post( '/users/', $params );
		switch ( $resp->getResponseCode() ) {
			case 201: return new RestAuthUser( $conn, $name );
			case 409: throw new RestAuthUserExists( $resp );
			case 412: throw new RestAuthPreconditionFailed( $resp );
			default:  throw new RestAuthUnknownStatus( $resp );
		}
	}

	/**
	 * Factory method that gets an existing user from RestAuth. This method
	 * verifies that the user exists and throws {@link RestAuthResourceNotFound}
	 * if not.
	 *
	 * @param RestAuthConnection $conn The connection to a RestAuth service.
	 * @param string $name The name of this user.
	 * @throws {@link RestAuthResourceNotFound} If the user does not exist in
	 *	RestAuth.
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
	public static function get( $conn, $name ) {
		$resp = $conn->get( '/users/' . $name . '/' );

		switch ( $resp->getResponseCode() ) {
			case 204: return new RestAuthUser( $conn, $name );
			case 404: throw new RestAuthResourceNotFound( $resp );
			default: throw new RestAuthUnknownStatus( $resp );
		}
	}

	/**
	 * Factory method that gets all users known to RestAuth.
	 *
	 * @param RestAuthConnection $conn The connection to a RestAuth service.
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
	public static function get_all( $conn ) {
		$resp = $conn->get( '/users/' );

		switch ( $resp->getResponseCode() ) {
			case 200:
				$response = array();
				foreach( json_decode( $resp->getBody() ) as $name ) {
					$response[] = new RestAuthUser( $conn, $name );
				}
				return $response;
			default: throw new RestAuthUnknownStatus( $resp );
		}
	}

	/**
	 * Constructor that initializes an object representing a user in
	 * RestAuth. 
	 *
	 * <b>Note:</b> The constructor does not verify if the user exists, use
	 * {@link get} or {@link get_all} if you wan't to be sure it exists.
	 *
	 * @param RestAuthConnection $conn The connection to a RestAuth service.
	 * @param string $name The name of this user.
	 */
	public function __construct( $conn, $name ) {
		$this->conn = $conn;
		$this->name = $name;
	}

	/**
	 * Set the password of this user.
	 *
	 * @param string $password The new password.
	 *
	 * @throws {@link RestAuthResourceNotFound} When the user does exist
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 *	parsed.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *      failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *      and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
	 */
	public function set_password( $password ) {
		$resp = $this->_put( $this->name, array( 'password' => $password ) );

		switch ( $resp->getResponseCode() ) {
			case 204: return;
			case 404: throw new RestAuthResourceNotFound( $resp );
			case 412: throw new RestAuthPreconditionFailed( $resp );
			default: throw new RestAuthUnknownStatus( $resp );
		}
	}

	/**
	 * Verify the given password.
	 * 
	 * The method does not throw an error if the user does not exist at all,
	 * it also returns false in this case.
	 * 
	 * @param string $password The password to verify.
	 * @return boolean true if the password is correct, false if the
	 * 	password is wrong or the user does not exist.
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 *	parsed.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *      failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *      and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
	 */
	public function verify_password( $password ) {
		$resp = $this->_post( $this->name, array( 'password' => $password ) );
		switch ( $resp->getResponseCode() ) {
			case 204: return true;
			case 404: return false;
			default: throw new RestAuthUnknownStatus( $resp );
		}
	}

	/**
	 * Delete this user.
	 * 
	 * @throws {@link RestAuthResourceNotFound} When the user does exist
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *      failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *      and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
	 */
	public function remove() {
		$resp = $this->_delete( $this->name );
		switch ( $resp->getResponseCode() ) {
			case 204: return;
			case 404: throw new RestAuthResourceNotFound( $resp );
			default: throw new RestAuthUnknownStatus( $resp );
		}
	}

	/**
	 * Get all properties defined for this user.
	 *
	 * This method causes a single request to the RestAuth service and is
	 * a much better solution when fetching multiple properties.
	 * 
	 * @return array A key/value array of the properties defined for this user.
	 * @throws {@link RestAuthResourceNotFound} When the user does exist
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *      failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *      and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
	 */
	public function get_properties() {
		$url = "$this->name/props/";
		$resp = $this->_get( $url );
		
		switch ( $resp->getResponseCode() ) {
			case 200:
				$props = (array) json_decode( $resp->getBody() );
				return $props;
			case 404: throw new RestAuthResourceNotFound( $resp );
			default: throw new RestAuthUnknownStatus( $resp );
		}
	}
	
	/**
	 * Set a property for this user. This method overwrites any previous
	 * entry.
	 *
	 * @param string $name The property to set.
	 * @param string $value The new value of the property.
	 * @throws {@link RestAuthResourceNotFound} When the user does exist
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 *	parsed.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *      failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *      and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
	 */
	public function set_property( $name, $value ) {
		$url = "$this->name/props/$name";
		$params = array( 'value' => $value );
		$resp = $this->_put( $url, $params );
		switch ( $resp->getResponseCode() ) {
			case 200: return json_decode( $resp->getBody() );
			case 201: return;
			case 404: throw new RestAuthResourceNotFound( $resp );
			default: throw new RestAuthUnknownStatus( $resp );
		}
	}


	/**
	 * Create a new property for this user. 
	 * 
	 * This method fails if the property already existed. Use {@link
	 * set_property} if you do not care if the property already exists.
	 * 
	 * @param string $name The property to set.
	 * @param string $value The new value of the property.
	 *
	 * @throws {@link RestAuthResourceNotFound} When the user does exist
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 *	parsed.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *      failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *      and authorization is not possible from this host.
	 * @throws {@link RestAuthPropertyExists} When the property already exists
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
	 */
	public function create_property( $name, $value ) {
		$url = "$this->name/props/";
		$params = array( 'prop' => $name, 'value' =>$value );
		$resp = $this->_post( $url, $params );
		switch ( $resp->getResponseCode() ) {
			case 201: return;
			case 404: throw new RestAuthResourceNotFound( $resp );
			case 409: throw new RestAuthPropertyExists( $resp );
			default: throw new RestAuthUnknownStatus( $resp );
		}
	}

	/**
	 * Get the given property for this user. 
	 *
	 * <b>Note:</b> Each call to this function causes an HTTP request to 
	 * the RestAuth service. If you want to get many properties, consider
	 * using {@link get_properties}.
	 *
	 * @param string $name Name of the property we should get.
	 * @return string The value of the property.
	 * @throws {@link RestAuthResourceNotFound} When the user does exist
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *      failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *      and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
	 */
	public function get_property( $name ) {
		$url = "$this->name/props/$name";
		$resp = $this->_get( $url );

		switch ( $resp->getResponseCode() ) {
			case 200: return json_decode( $resp->getBody() );
			case 404: throw new RestAuthResourceNotFound( $resp );
			default: throw new RestAuthUnknownStatus( $resp );
		}
	}

	/**
	 * Delete the named property.
	 *
	 * @param string $name Name of the property that should be deleted.
	 * @throws {@link RestAuthResourceNotFound} When the user does exist
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *      failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *      and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
	 */
	public function remove_property( $name ) {
		$url = "$this->name/props/$name";
		$resp = $this->_delete( $url );

		switch ( $resp->getResponseCode() ) {
			case 204: return;
			case 404: throw new RestAuthResourceNotFound( $resp );
			default: throw new RestAuthUnknownStatus( $resp );
		}
	}
}
?>
