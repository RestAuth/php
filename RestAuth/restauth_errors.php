<?php

/**
 * This file collects all exceptions not directly related to a RestAuthUser or
 * a RestAuthGroup
 *
 * PHP version 5.1
 *
 * LICENSE: This file is part of php-restauth.
 *
 * php-restauth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * php-restauth is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * php-restauth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @link      https://php.restauth.net
 */

/**
 * Common superclass for all RestAuth related exceptions.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
abstract class RestAuthException extends Exception
{
    /**
     * Constructor
     *
     * @param HttpResponse $response The response causing this exception.
     */
    public function __construct($response)
    {
        $this->message = $response->getBody();
        $this->response = $response;
    }
}

/**
 * Superclass for exceptions thrown when a resource queried is not found.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthResourceNotFound extends RestAuthException
{
    protected $code = 404;

    /**
     * Get the value of the Resource-Type header.
     *
     * @return str
     */
    public function getType()
    {
        return $this->response->getHeader('Resource-Type');
    }
}

/**
 * Superclass of exceptions thrown when a resource is supposed to be created but
 * already exists.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
abstract class RestAuthResourceConflict extends RestAuthException
{
    protected $code = 409;
}

/**
 * Exception thrown when a response was unparsable.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthBadResponse extends RestAuthException
{
}

/**
 * Superclass for service-related errors.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthInternalException extends RestAuthException
{
}

/**
 * Thrown when the RestAuth service cannot parse the HTTP request. On a protocol
 * level, this corresponds to a HTTP status code 400.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthBadRequest extends RestAuthInternalException
{
    protected $code = 400;
}

/**
 * Thrown when the RestAuth service suffers an internal error. On a protocol
 * level, this corresponds to a HTTP status code 500.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthInternalServerError extends RestAuthInternalException
{
    protected $code = 500;
}

/**
 * Thrown when an unknown HTTP status code is encountered. This should never
 * really happen and usually indicates a bug in the library.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthUnknownStatus extends RestAuthInternalException
{
}

/**
 * Thrown when you send unacceptable data to the RestAuth service, i.e. a
 * password that is too short.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthPreconditionFailed extends RestAuthException
{
    protected $code = 412;
}

/**
 * Thrown when the user/password does not match the registered service.
 *
 * On a protocol level, this corresponds to the HTTP status code 401.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthUnauthorized extends RestAuthException
{
    protected $code = 401;
}

/**
 * Thrown when the RestAuth server cannot generate a response in the requested
 * format.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthNotAcceptable extends RestAuthInternalException
{
    protected $code = 406;
}

/**
 * Thrown when the RestAuth server does not understand the content-type sent by
 * this library.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthUnsupportedMediaType extends RestAuthInternalException
{
    protected $code = 415;
}

/**
 * Thrown when a connection-related error occurs (i.e. the RestAuth service is
 * not available).
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthHttpException extends RestAuthException
{
    /**
     * Constructor.
     *
     * @param Exception $http_exception The exception causing this exception.
     */
    public function __construct()
    {
    }

    /**
     * Get the root cause of this exception.
     *
     * @return Exception
     */
    public function getCause()
    {
        return $this->cause;
    }
}

?>
