<?php
/**
 * This file contains classes used for user handling.
 *
 * PHP version 5.1
 *
 * LICENSE: php-restauth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * php-restauth is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with php-restauth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/lgpl.html  GNU LESSER GENERAL PUBLIC LICENSE
 * @version   0.0
 * @link      https://php.restauth.net
 */

/**
 * General includes
 */
require_once 'restauth_errors.php';
require_once 'restauth_common.php';
require_once 'restauth_groups.php';

/**
 * Thrown when a user is supposed to be created but already exists.
 * 
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/lgpl.html  GNU LESSER GENERAL PUBLIC LICENSE
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthUserExists extends RestAuthResourceConflict
{
}

/**
 * Thrown when a property is supposed to be created but already exists.
 * 
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/lgpl.html  GNU LESSER GENERAL PUBLIC LICENSE
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthPropertyExists extends RestAuthResourceConflict
{
}

/**
 * This class acts as a frontend for actions related to users.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/lgpl.html  GNU LESSER GENERAL PUBLIC LICENSE
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthUser extends RestAuthResource
{
    const PREFIX = '/users/';

    /**
     * Factory method that creates a new user in the RestAuth database and
     * throws {@link RestAuthUserExists} if the user already exists.
     *
     * @param RestAuthConnection $conn     The connection to a RestAuth service.
     * @param string             $name     The name of this user.
     * @param string             $password The password for the new user. If
     *    ommitted or an empty string, the account is created but disabled.
     *
     * @return RestAuthUser An instance representing a new user.
     *
     * @throws {@link RestAuthBadRequest} When the request body could not be
     *     parsed.
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthUserExists} If the user already exists.
     * @throws {@link RestAuthPreconditionFailed} When username or password is
     *    invalid.
     * @throws {@link RestAuthUnsupportedMediaType} The server does not
     *    support the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is
     *    unknown.
     */
    public static function create($conn, $name, $password=null)
    {
        $params = array('user' => $name);
        if (!((is_null($password)) || ($password === ''))) {
            $params['password'] = $password;
        }
        $resp = $conn->post('/users/', $params);
        switch ($resp->getResponseCode()) {
        case 201:
            return new RestAuthUser($conn, $name);
            
        case 409:
            throw new RestAuthUserExists($resp);
            
        case 412:
            throw new RestAuthPreconditionFailed($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Factory method that gets an existing user from RestAuth. This method
     * verifies that the user exists and throws {@link RestAuthResourceNotFound}
     * if not.
     *
     * @param RestAuthConnection $conn The connection to a RestAuth service.
     * @param string             $name The name of this user.
     *
     * @return RestAuthUser An instance representing a remote user.
     * 
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthResourceNotFound} If the user does not exist in
     *    RestAuth.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is
     *    unknown.
     */
    public static function get($conn, $name)
    {
        $resp = $conn->get('/users/' . $name . '/');

        switch ($resp->getResponseCode()) {
        case 204:
            return new RestAuthUser($conn, $name);
            
        case 404:
            throw new RestAuthResourceNotFound($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Factory method that gets all users known to RestAuth.
     *
     * @param RestAuthConnection $conn The connection to a RestAuth service.
     *
     * @return array An array containing all users.
     * 
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is
     *    unknown.
     */
    public static function get_all($conn)
    {
        $resp = $conn->get('/users/');

        switch ($resp->getResponseCode()) {
        case 200:
            $response = array();
            foreach (json_decode($resp->getBody()) as $name) {
                $response[] = new RestAuthUser($conn, $name);
            }
            return $response;
        
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Constructor that initializes an object representing a user in
     * RestAuth. 
     *
     * <b>Note:</b> The constructor does not verify if the user exists, use
     * {@link get} or {@link get_all} if you wan't to be sure it exists.
     *
     * @param RestAuthConnection $conn The connection to a RestAuth service.
     * @param string             $name The name of this user.
     */
    public function __construct($conn, $name)
    {
        $this->conn = $conn;
        $this->name = $name;
    }

    /**
     * Set the password of this user.
     *
     * @param string $password The new password. If ommitted or an empty
     *    string, the account is disabled.
     *
     * @return null
     *
     * @throws {@link RestAuthBadRequest} When the request body could not be
     *    parsed.
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthResourceNotFound} When the user does exist
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthPreconditionFailed} When password is invalid.
     * @throws {@link RestAuthUnsupportedMediaType} The server does not
     *    support the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function setPassword($password=null)
    {
        $params = array();
        if (!((is_null($password)) || ($password === ''))) {
            $params['password'] = $password;
        }
        $resp = $this->_put($this->name, $params);

        switch ($resp->getResponseCode()) {
        case 204:
            return;
        
        case 404:
            throw new RestAuthResourceNotFound($resp);
            
        case 412:
            throw new RestAuthPreconditionFailed($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Verify the given password.
     * 
     * The method does not throw an error if the user does not exist at all,
     * it also returns false in this case.
     * 
     * @param string $password The password to verify.
     * 
     * @return boolean true if the password is correct, false if the
     *     password is wrong or the user does not exist.

     * @throws {@link RestAuthBadRequest} When the request body could not be
     *    parsed.
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthUnsupportedMediaType} The server does not
     *    support the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function verify_password($password)
    {
        $resp = $this->_post($this->name, array('password' => $password));
        switch ($resp->getResponseCode()) {
        case 204:
            return true;
        
        case 404:
            return false;
        
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Delete this user.
     *
     * @return null
     * 
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthResourceNotFound} When the user does exist
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function remove()
    {
        $resp = $this->_delete($this->name);
        switch ($resp->getResponseCode()) {
        case 204:
            return;
        
        case 404:
            throw new RestAuthResourceNotFound($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Get all properties defined for this user.
     *
     * This method causes a single request to the RestAuth service and is
     * a much better solution when fetching multiple properties.
     * 
     * @return array A key/value array of the properties defined for this user.
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthResourceNotFound} When the user does exist
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function get_properties()
    {
        $url = "$this->name/props/";
        $resp = $this->_get($url);
        
        switch ($resp->getResponseCode()) {
        case 200:
            $props = (array) json_decode($resp->getBody());
            return $props;
        
        case 404:
            throw new RestAuthResourceNotFound($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }
    
    /**
     * Set a property for this user. This method overwrites any previous
     * entry.
     *
     * @param string $name  The property to set.
     * @param string $value The new value of the property.
     *
     * @return mixed null if this was a new property or a string representing
     *    the value of a previously set property.
     *
     * @throws {@link RestAuthBadRequest} When the request body could not be
     *    parsed.
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthResourceNotFound} When the user does exist
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthUnsupportedMediaType} The server does not
     *    support the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function set_property($name, $value)
    {
        $url = "$this->name/props/$name"; 
        $params = array('value' => $value);
        $resp = $this->_put($url, $params);
        switch ($resp->getResponseCode()) {
        // todo: 200 is never tested!!!
        case 200:
            return $this->conn->handler->unmarshal_str($resp->getBody());
            
        case 201:
            return;
        
        case 404:
            throw new RestAuthResourceNotFound($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }


    /**
     * Create a new property for this user. 
     * 
     * This method fails if the property already existed. Use {@link
     * set_property} if you do not care if the property already exists.
     * 
     * @param string $name  The property to set.
     * @param string $value The new value of the property.
     *
     * @return null
     *
     * @throws {@link RestAuthBadRequest} When the request body could not be
     *    parsed.
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthResourceNotFound} When the user does exist
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthPropertyExists} When the property already exists
     * @throws {@link RestAuthUnsupportedMediaType} The server does not
     *    support the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function create_property($name, $value)
    {
        $url = "$this->name/props/";
        $params = array('prop' => $name, 'value' =>$value);
        $resp = $this->_post($url, $params);
        switch ($resp->getResponseCode()) {
        case 201:
            return;
        
        case 404:
            throw new RestAuthResourceNotFound($resp);
            
        case 409:
            throw new RestAuthPropertyExists($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Get the given property for this user. 
     *
     * <b>Note:</b> Each call to this function causes an HTTP request to 
     * the RestAuth service. If you want to get many properties, consider
     * using {@link get_properties}.
     *
     * @param string $name Name of the property we should get.
     * 
     * @return string The value of the property.
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthResourceNotFound} When the user does exist
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function get_property($name)
    {
        $url = "$this->name/props/$name";
        $resp = $this->_get($url);

        switch ($resp->getResponseCode()) {
        case 200:
            return $this->conn->handler->unmarshal_str($resp->getBody());
            
        case 404:
            throw new RestAuthResourceNotFound($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Delete the named property.
     *
     * @param string $name Name of the property that should be deleted.
     *
     * @return null
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthResourceNotFound} When the user does exist
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function remove_property($name)
    {
        $url = "$this->name/props/$name";
        $resp = $this->_delete($url);

        switch ($resp->getResponseCode()) {
        case 204:
            return;
        
        case 404:
            throw new RestAuthResourceNotFound($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Get all groups that this user is a member of.
     *
     * This method is just a shortcut for {@link RestAuthGroup::get_all()}.
     * 
     * @return array Array of {@link RestAuthGroup groups}.
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthResourceNotFound} When the user does not
     *    exist.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is
     *    unknown.
     */
    public function get_groups()
    {
        return RestAuthGroup::get_all($this->conn, $this);
    }

    /**
     * Check if the user is a member in the given group.
     *
     * This method is just a shortcut for {@link
     * RestAuthGroup::is_member()}.
     *
     * @param mixed $group The group to test. Either a  {@link RestAuthGroup}
     *    or a string representing the groupname.
     *    
     * @return boolean true if the user is a member, false if not
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthResourceNotFound} When the group does not
     *    exist.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is
     *    unknown.
     */
    public function in_group($group)
    {
        if (is_string($group)) {
            $group = new RestAuthGroup($this->conn, $group);
        }
        
        return $group->is_member($this);
    }

    /**
     * Make this user a member of the given group.
     *
     * This method is just a shortcut for {@link RestAuthGroup::add_user()}.
     *
     * @param mixed $group The group the user should become a member of. 
     *    Either a  {@link RestAuthGroup} or a string representing the
     *    groupname.
     *
     * @return null
     *
     * @throws {@link RestAuthBadRequest} When the request body could not be
     *    parsed.
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthResourceNotFound} When the group does not
     *    exist.
     * @throws {@link RestAuthUnsupportedMediaType} The server does not
     *    support the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is
     *    unknown.
     */
    public function add_group($group)
    {
        if (is_string($group)) {
            $group = new RestAuthGroup($this->conn, $group);
        }
        
        return $group->add_user($this);
    }

    /**
     * Remove the users membership from the given group.
     *
     * This method is just a shortcut for {@link 
     * RestAuthGroup::remove_user()}.
     *
     * @param mixed $group The group the user should no longer be a member
     *    of. Either a  {@link RestAuthGroup} or a string representing the
     *    groupname.
     *
     * @return null
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthResourceNotFound} When the group or user does
     *    not exist.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is
     *    unknown.
     */
    public function remove_group($group)
    {
        if (is_string($group)) {
            $group = new RestAuthGroup($this->conn, $group);
        }
        
        return $group->remove_user($this);
    }

    /**
     * Compare two instances by their username.
     *
     * @param RestAuthUser $a The first user
     * @param RestAuthUser $b The second user
     *
     * @return int 0 if the Users are equal, 1 if a is greater then b, -1
     *     otherwise.
     */
    public static function cmp($a, $b)
    {
        $aName = $a->name;
        $bName = $b->name;
        if ($aName == $bName) {
            return 0;
        } else {
            return ($aName > $bName) ? +1 : -1;
        }
    }
    
    /**
     * A string representic this class for pretty-printing.
     *
     * @return str A string representation of this class.
     */
    function __toString()
    {
        return "<User: " . $this->name . ">";
    }
}
?>
