<?php

require_once( 'restauth_errors.php' );
require_once( 'restauth_common.php' );

/**
 * Thrown when a user queried is not found.
 */
class RestAuthUserNotFound extends RestAuthResourceNotFound {
}

/**
 * Thrown when a property queried is not found.
 */
class RestAuthPropertyNotFound extends RestAuthResourceNotFound {
}

/**
 * Thrown when a user is supposed to be created but already exists.
 */
class RestAuthUserExists extends RestAuthResourceConflict {
}

/**
 * Thrown when a property is supposed to be created but already exists.
 */
class RestAuthPropertyExists extends RestAuthResourceConflict {
}

/**
 * Factory method that creates a new user in the RestAuth database.
 *
 * @param RestAuthConnection $conn The connection to a RestAuth service.
 * @param string $name The name of this user.
 * @param string $password The password for the new user
 * @throws {@link RestAuthUserExists} If the user already exists.
 * @throws {@link RestAuthDataUnacceptable} When username or password is invalid.
 * @throws {@link RestAuthInternalServerError} When the RestAuth service returns
 *	HTTP status code 500
 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
 */
function RestAuthCreateUser( $conn, $name, $password ) {
	$params = array( 'user' => $name, 'password' => $password );
	$resp = $conn->post( '/users/', $params );
	switch ( $resp->code ) {
		case 201: return new RestAuthUser( $conn, $name );
		case 409: throw new RestAuthUserExists();
		case 412: throw new RestAuthDataUnacceptable();
		default:  throw new RestAuthUnknownStatus();
	}
}

/**
 * Factory method that gets an existing user from RestAuth. This method verifies
 * that the user exists in the RestAuth and throws UserNotFound if not.
 *
 * @param RestAuthConnection $conn The connection to a RestAuth service.
 * @param string $name The name of this user.
 * @throws {@link RestAuthUserNotFound} If the user does not exist in RestAuth.
 * @throws {@link RestAuthInternalServerError} When the RestAuth service returns
 *	HTTP status code 500
 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
 */
function RestAuthGetUser( $conn, $name ) {
	$resp = $conn->get( '/users/' . $name . '/' );

	switch ( $resp->code ) {
		case 200: return new RestAuthUser( $conn, $name );
		case 404: throw new RestAuthUserNotFound();
		default: throw new RestAuthUnknownStatus();
	}
}

/**
 * Factory method that gets all users known to RestAuth.
 *
 * @param RestAuthConnection $conn The connection to a RestAuth service.
 * @throws {@link RestAuthInternalServerError} When the RestAuth service returns
 *	HTTP status code 500
 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
 */
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

/**
 * This class acts as a frontend for actions related to users.
 *
 * @see RestAuthGetUser
 * @see RestAuthGetAllUsers
 * @see RestAuthCreateUser
 */
class RestAuthUser extends RestAuthResource {
	/**
	 * Constructor that initializes an object representing a user in
	 * RestAuth. The constructor does not verify if the user exists, use
	 * {@link RestAuthGetUser} or {@link RestAuthGetAllUsers} if you wan't
	 * to be sure it exists.
	 *
	 * @param RestAuthConnection $conn The connection to a RestAuth service.
	 * @param string $name The name of this user.
	 */
	function __construct( $conn, $name ) {
		$this->prefix = '/users/';
		$this->conn = $conn;
		$this->name = $name;
	}

	/**
	 * Set the password of this user.
	 *
	 * @param string $password The new password.
	 *
	 * @throws {@link RestAuthUserNotFound} When the user does exist
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
	function set_password( $password ) {
		$resp = $this->put( $this->name, array( 'password' => $password ) );

		switch ( $resp->code ) {
			case 200: return;
			case 404: throw new RestAuthUserNotFound();
			default: throw new RestAuthUnknownStatus();
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
	function verify_password( $password ) {
		$resp = $this->post( $this->name, array( 'password' => $password ) );
		switch ( $resp->code ) {
			case 200: return true;
			case 404: return false;
			default: throw new RestAuthUnknownStatus();
		}
	}

	/**
	 * Delete this user.
	 * 
	 * @throws {@link RestAuthUserNotFound} When the user does exist
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *      failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *      and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
	 */
	function remove() {
		$resp = $this->delete( $this->name );
		switch ( $resp->code ) {
			case 200: return;
			case 404: throw new RestAuthUserNotFound();
			default: throw new RestAuthUnknownStatus();
		}
	}

	/**
	 * Get all properties defined for this user.
	 * 
	 * @return array A key/value array of the properties defined for this user.
	 * @throws {@link RestAuthUserNotFound} When the user does exist
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *      failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *      and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
	 */
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

	/**
	 * Create a new property for this user. This method fails if the
	 * property already existed. Use L{set_property} if you do not care
	 * if the property already exists.
	 *
	 * @param string $name The property to set.
	 * @param string $value The new value of the property.
	 *
	 * @throws {@link RestAuthUserNotFound} When the user does exist
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

	/**
	 * Set a property for this user. This method overwrites and previous
	 * entry.
	 *
	 * @param string $name The property to set.
	 * @param string $value The new value of the property.
	 * @throws {@link RestAuthUserNotFound} When the user does exist
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

	/**
	 * Get the given property for this user.
	 *
	 * @return string The value of the property.
	 * @throws {@link RestAuthUserNotFound} When the user does exist
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *      failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *      and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
	 */
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

	/**
	 * Delete the given property.
	 *
	 * @throws {@link RestAuthUserNotFound} When the user does exist
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 *      failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *      and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	returns HTTP status code 500
	 * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
	 */
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
