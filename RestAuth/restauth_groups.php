<?php
/**
 * Code related to RestAuthGroup handling.
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
 * imports required for the code here.
 */
require_once 'restauth_common.php';
require_once 'restauth_users.php';

/**
 * Thrown when a group that is supposed to be created already exists.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/lgpl.html  GNU LESSER GENERAL PUBLIC LICENSE
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthGroupExists extends RestAuthResourceConflict
{
}

/**
 * This class acts as a frontend for actions related to groups.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/lgpl.html  GNU LESSER GENERAL PUBLIC LICENSE
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthGroup extends RestAuthResource
{
    const PREFIX = '/groups/';

    /**
     * Factory method that creates a new group in RestAuth.
     * 
     * @param RestAuthConnection $conn A connection to a RestAuth service.
     * @param string             $name The name of the new group.
     *
     * @return RestAuthGroup An instance representing a remote group.
     *
     * @throws {@link RestAuthBadRequest} When the request body could not be
     *    parsed.
     * @throws {@link RestAuthUnauthorized} When service authentication failed.
     * @throws {@link RestAuthGroupExists} If the group already exists.
     * @throws {@link RestAuthPreconditionFailed} When the groupname is invalid.
     * @throws {@link RestAuthUnsupportedMediaType} The server does not support
     *    the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public static function create($conn, $name)
    {
        $resp = $conn->post('/groups/', array('group' => $name));
        switch ($resp->getResponseCode()) {
        case 201:
            return new RestAuthGroup($conn, $name);
            
        case 409:
            throw new RestAuthGroupExists($resp);
            
        case 412:
            throw new RestAuthPreconditionFailed($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Factory method that creates a {@link RestAuthGroup} and verifies that it
     * exists.
     * 
     * @param RestAuthConnection $conn A connection to a RestAuth service.
     * @param string             $name The name of the new group.
     *
     * @return RestAuthGroup An instance representing a remote group.
     *
     * @throws {@link RestAuthUnauthorized} When service authentication failed.
     * @throws {@link RestAuthResourceNotFound} When the group does not exist.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public static function get($conn, $name)
    {
        $resp = $conn->get('/groups/' . $name . '/');
        switch ($resp->getResponseCode()) {
        case 204:
            return new RestAuthGroup($conn, $name);
            
        case 404:
            throw new RestAuthResourceNotFound($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Factory method that gets all groups for this service known to RestAuth.
     *
     * @param RestAuthConnection $conn A connection to a RestAuth service.
     * @param mixed              $user Limit the output to groups where the
     *    given user is a member of. Either a {@link RestAuthUser} or a string
     *    representing the name of that user.
     *
     * @return array Array of {@link RestAuthGroup groups}.
     *
     * @throws {@link RestAuthUnauthorized} When service authentication failed.
     * @throws {@link RestAuthResourceNotFound} When the user does not exist.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate a
     *    response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public static function getAll($conn, $user=null)
    {
        $params = array();
        if ($user) {
            if (is_string($user)) {
                $params['user'] = $user;
            } else {
                $params['user'] = $user->name;
            }
        }
    
        $resp = $conn->get('/groups/', $params);
        switch ($resp->getResponseCode()) {
        case 200: 
            $groups = array();
            foreach (json_decode($resp->getBody()) as $groupname) {
                $groups[] = new RestAuthGroup($conn, $groupname);
            }
            return $groups;
        
        case 404:
            throw new RestAuthResourceNotFound($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Constructor that initializes an object representing a group in RestAuth.
     * The constructor does not make sure the group exists. 
     * 
     * @param RestAuthConnection $conn A connection to a RestAuth service.
     * @param string             $name The name of the new group.
     */
    public function __construct($conn, $name)
    {
        $this->conn = $conn;
        $this->name = $name;
    }

    /**
     * Get all members of this group.
     *
     * @return array Array of {@link RestAuthUser users}.
     *
     * @throws {@link RestAuthUnauthorized} When service authentication failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate a
     *    response in the content type used by this connection.
     * @throws {@link RestAuthResourceNotFound} When the group does not exist.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function get_members()
    {
        $params = array();

        $resp = $this->_get($this->name . '/users/', $params);
        switch ($resp->getResponseCode()) {
        case 200: 
            $users = array();
            foreach (json_decode($resp->getBody()) as $username) {
                $users[] = new RestAuthUser($this->conn, $username);
            }
            return $users;
        
        case 404:
            throw new RestAuthResourceNotFound($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Add a user to this group.
     *
     * @param mixed $user The user to add. Either a {@link RestAuthUser} or a
     *    string.
     *
     * @return null
     *
     * @throws {@link RestAuthBadRequest} When the request body could not be
     *    parsed.
     * @throws {@link RestAuthUnauthorized} When service authentication failed.
     * @throws {@link RestAuthResourceNotFound} When the group does not exist.
     * @throws {@link RestAuthUnsupportedMediaType} The server does not support
     *    the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function add_user($user)
    {
        if (is_string($user)) {
            $params = array('user' => $user);
        } else {
            $params = array('user' => $user->name);
        }

        $resp = $this->_post($this->name . '/users/', $params);
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
     * Check if the named user is a member.
     *
     * @param mixed $user The user to test. Either a  {@link RestAuthUser} or a
     *    string representing the username.
     *    
     * @return boolean true if the user is a member, false if not
     *
     * @throws {@link RestAuthUnauthorized} When service authentication failed.
     * @throws {@link RestAuthResourceNotFound} When the group does not exist.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function isMember($user)
    {
        if (is_string($user)) {
            $username = $user;
        } else {
            $username = $user->name;
        }

        $url = $this->name . '/users/' . $username;
        $resp = $this->_get($url);

        switch ($resp->getResponseCode()) {
        case 204:
            return true;
        
        case 404:
            switch ($resp->getHeader('Resource-Type')) {
            case 'user':
                return false;
            
            default:
                throw new RestAuthResourceNotFound($resp);
            }
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Delete this group.
     *
     * @return null
     *
     * @throws {@link RestAuthUnauthorized} When service authentication failed.
     * @throws {@link RestAuthResourceNotFound} When the group does not exist.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *     returns HTTP status code 500
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
     * Remove the given user from the group.
     *
     * @param mixed $user The user to remove. Either a {@link RestAuthUser} or a
     * string the username.
     *
     * @return null
     *
     * @throws {@link RestAuthUnauthorized} When service authentication failed.
     * @throws {@link RestAuthResourceNotFound} When the group or user does not
     *    exist.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function removeUser($user)
    {
        if (is_string($user)) {
            $username = $user;
        } else {
            $username = $user->name;
        }

        $url = $this->name . '/users/' . $username;
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
     * Add a group to this group.
     *
     * @param mixed $group The group to add. Either a {@link RestAuthGroup} or a
     *    string representing the groupname.
     *
     * @return null
     *
     * @throws {@link RestAuthBadRequest} When the request body could not be
     *    parsed.
     * @throws {@link RestAuthUnauthorized} When service authentication failed.
     * @throws {@link RestAuthResourceNotFound} When either this group or
     *    the subgroup does not exist.
     * @throws {@link RestAuthUnsupportedMediaType} The server does not support
     *    the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function addGroup($group)
    {
        if (is_string($group)) {
            $groupname = $group;
        } else {
            $groupname = $group->name;
        }

        $params = array('group' => $groupname);
        
        $resp = $this->_post($this->name . '/groups/', $params);
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
     * Get a list of subgroups of this groups.
     *
     * @return array Array of {@link RestAuthGroup groups}.
     *
     * @throws {@link RestAuthUnauthorized} When service authentication failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate a
     *    response in the content type used by this connection.
     * @throws {@link RestAuthResourceNotFound} When the group does not exist.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *     returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function getGroups()
    {
        $resp = $this->_get($this->name . '/groups/');
        switch ($resp->getResponseCode()) {
        case 200: 
            $users = array();
            foreach (json_decode($resp->getBody()) as $username) {
                $users[] = new RestAuthGroup($this->conn, $username);
            }
            return $users;
        
        case 404:
            throw new RestAuthResourceNotFound($resp);
            
            // @codeCoverageIgnoreStart
        default:
            throw new RestAuthUnknownStatus($resp);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Remove a subgroup from this group.
     *
     * @param mixed $group The group to remove. Either a 
     *    {@link RestAuthGroup} or a string representing the groupname.
     *
     * @return null
     *
     * @throws {@link RestAuthUnauthorized} When service authentication failed.
     * @throws {@link RestAuthResourceNotFound} When the group or subgroup does
     *    not exist.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    returns HTTP status code 500
     * @throws {@link RestAuthUnknownStatus} If the response status is unknown.
     */
    public function removeGroup($group)
    {
        if (is_string($group)) {
            $groupname = $group;
        } else {
            $groupname = $group->name;
        }

        $url = $this->name . '/groups/' . $groupname . '/';
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
}

?>
