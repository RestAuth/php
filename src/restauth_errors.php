<?php

/**
 * Common superclass for all RestAuth related exceptions.
 */
class RestAuthException extends Exception {
}

/**
 * Superclass for exceptions thrown when a resource queried is not found.
 */
class RestAuthResourceNotFound extends RestAuthException {
}

/**
 * Superclass for service-related errors.
 */
class RestAuthInternalException extends RestAuthException {
}

/**
 * Thrown when the RestAuth service cannot parse the HTTP request. On a protocol
 * level, this corresponds to a HTTP status code 400.
 */
class RestAuthBadRequest extends RestAuthInternalException {
}

/**
 * Thrown when the RestAuth service suffers an internal error. On a protocol
 * level, this corresponds to a HTTP status code 500.
 */
class RestAuthInternalServerError extends RestAuthInternalException {
}

/**
 * Thrown when an unknown HTTP status code is encountered. This should never
 * really happen and usually indicates a bug in the library.
 */
class RestAuthUnknownStatus extends RestAuthInternalException {
}

/**
 * Superclass of exceptions thrown when a resource is supposed to be created but
 * already exists.
 */
class RestAuthResourceConflict extends RestAuthException { 
}

/**
 * Thrown when you send unacceptable data to the RestAuth service, i.e. a
 * password that is too short.
 */
class RestAuthDataUnacceptable extends RestAuthException {
}

/**
 * Superclass for exceptions related to access for this service.
 */
class RestAuthServiceAuthorizationException extends RestAuthException {
}

/**
 * Thrown when the user/password does not match the registered service.
 *
 * On a protocol level, this corresponds to the HTTP status code 401.
 */
class RestAuthUnauthorized extends RestAuthServiceAuthorizationException {
}

/**
 * Thrown when service authentication failed and is not possible from this host.
 *
 * On a protocol level, this corresponds to the HTTP status code 403.
 */
class RestAuthForbidden extends RestAuthServiceAuthorizationException {
}

?>
