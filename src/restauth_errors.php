<?php

/**
 * This file collects all exceptions not directly related to a RestAuthUser or
 * a RestAuthGroup
 *
 * @package php-restauth
 */

/**
 * Common superclass for all RestAuth related exceptions.
 *
 * @package php-restauth
 */
abstract class RestAuthException extends Exception {
	public function __construct( $response, $message = '' ) {
		if ( $message ) {
			$this->message = $message;
		} else {
			$this->message = $response->body;
		}
	}
}

/**
 * Superclass for exceptions thrown when a resource queried is not found.
 *
 * @package php-restauth
 */
class RestAuthResourceNotFound extends RestAuthException {
	protected $code = 404;
}

/**
 * Exception thrown when a response was unparsable.
 *
 * @package php-restauth
 */
class RestAuthBadResponse extends RestAuthException{}

/**
 * Superclass for service-related errors.
 *
 * @package php-restauth
 */
class RestAuthInternalException extends RestAuthException {}

/**
 * Thrown when the RestAuth service cannot parse the HTTP request. On a protocol
 * level, this corresponds to a HTTP status code 400.
 *
 * @package php-restauth
 */
class RestAuthBadRequest extends RestAuthInternalException {}

/**
 * Thrown when the RestAuth service suffers an internal error. On a protocol
 * level, this corresponds to a HTTP status code 500.
 *
 * @package php-restauth
 */
class RestAuthInternalServerError extends RestAuthInternalException {}

/**
 * Thrown when an unknown HTTP status code is encountered. This should never
 * really happen and usually indicates a bug in the library.
 *
 * @package php-restauth
 */
class RestAuthUnknownStatus extends RestAuthInternalException {}

/**
 * Superclass of exceptions thrown when a resource is supposed to be created but
 * already exists.
 *
 * @package php-restauth
 */
class RestAuthResourceConflict extends RestAuthException {}

/**
 * Thrown when you send unacceptable data to the RestAuth service, i.e. a
 * password that is too short.
 *
 * @package php-restauth
 */
class RestAuthDataUnacceptable extends RestAuthException {}

/**
 * Superclass for exceptions related to access for this service.
 *
 * @package php-restauth
 */
class RestAuthServiceAuthorizationException extends RestAuthException {}

/**
 * Thrown when the user/password does not match the registered service.
 *
 * On a protocol level, this corresponds to the HTTP status code 401.
 *
 * @package php-restauth
 */
class RestAuthUnauthorized extends RestAuthServiceAuthorizationException {}

/**
 * Thrown when service authentication failed and is not possible from this host.
 *
 * On a protocol level, this corresponds to the HTTP status code 403.
 *
 * @package php-restauth
 */
class RestAuthForbidden extends RestAuthServiceAuthorizationException {}

?>
