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
	public function __construct( $response ) {
		$this->message = $response->getBody();
		$this->response = $response;
	}
}

/**
 * Superclass for exceptions thrown when a resource queried is not found.
 *
 * @package php-restauth
 */
class RestAuthResourceNotFound extends RestAuthException {
	protected $code = 404;

	public function get_type() {
		return $this->response->getHeader( 'Resource-Type' );
	}
}

/**
 * Superclass of exceptions thrown when a resource is supposed to be created but
 * already exists.
 *
 * @package php-restauth
 */
abstract class RestAuthResourceConflict extends RestAuthException {
	protected $code = 409;
}

/**
 * Exception thrown when a response was unparsable.
 *
 * @package php-restauth
 */
class RestAuthBadResponse extends RestAuthException{
}

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
class RestAuthBadRequest extends RestAuthInternalException {
	protected $code = 400;
}

/**
 * Thrown when the RestAuth service suffers an internal error. On a protocol
 * level, this corresponds to a HTTP status code 500.
 *
 * @package php-restauth
 */
class RestAuthInternalServerError extends RestAuthInternalException {
	protected $code = 500;
}

/**
 * Thrown when an unknown HTTP status code is encountered. This should never
 * really happen and usually indicates a bug in the library.
 *
 * @package php-restauth
 */
class RestAuthUnknownStatus extends RestAuthInternalException {
}

/**
 * Thrown when you send unacceptable data to the RestAuth service, i.e. a
 * password that is too short.
 *
 * @package php-restauth
 */
class RestAuthPreconditionFailed extends RestAuthException {
	protected $code = 412;
}

/**
 * Thrown when the user/password does not match the registered service.
 *
 * On a protocol level, this corresponds to the HTTP status code 401.
 *
 * @package php-restauth
 */
class RestAuthUnauthorized extends RestAuthException {
	protected $code = 401;
}

class RestAuthNotAcceptable extends RestAuthInternalException {
	protected $code = 406;
}

class RestAuthUnsupportedMediaType extends RestAuthInternalException {
	protected $code = 415;
}


?>
