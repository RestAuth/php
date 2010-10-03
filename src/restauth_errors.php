<?php

class RestAuthException extends Exception {
}

class RestAuthResourceNotFound extends RestAuthException {
}

class RestAuthInternalException extends RestAuthException {
}

class RestAuthBadRequest extends RestAuthInternalException {
}

class RestAuthInternalServerError extends RestAuthInternalException {
}

class RestAuthUnknownStatus extends RestAuthInternalException {
}

class RestAuthResourceConflict extends RestAuthException { 
}

class RestAuthDataUnacceptable extends RestAuthException {
}

?>
