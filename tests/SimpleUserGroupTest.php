<?php
/**
 * This file does some very basic group tests.
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
 * @category   Testing
 * @package    RestAuth
 * @subpackage Testing
 * @author     Mathias Ertl <mati@restauth.net>
 * @copyright  2010-2011 Mathias Ertl
 * @license    http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version    0.5.0
 * @link       https://php.restauth.net
 */

require_once 'RestAuth/restauth.php';

// variables are defined in UserTest.php
 
/**
 * Do some very basic group tests.
 *
 * @category   Testing
 * @package    RestAuth
 * @subpackage Testing
 * @author     Mathias Ertl <mati@restauth.net>
 * @copyright  2010-2011 Mathias Ertl
 * @license    http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version    Release: 0.5.0
 * @link       https://php.restauth.net
 */
class SimpleUserGroupTest extends PHPUnit_Framework_TestCase
{
    /**
     * Set up the data for the tests.
     *
     * @return null
     */
    public function setUp()
    {
        global $user, $username1, $group, $groupname1;

        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $this->conn = RestAuthConnection::getConnection(
            $RestAuthHost, $RestAuthUser, $RestAuthPass
        );

        $users = RestAuthUser::getAll($this->conn);
        if (count($users)) {
            throw new Exception("Found " . count($users) . " left over users.");
        }
        $groups = RestAuthGroup::getAll($this->conn);
        if (count($groups)) {
            throw new Exception("Found " . count($groups) . " left over users.");
        }

        $user = RestAuthUser::create($this->conn, $username1, "foobar");
        $group = RestAuthGroup::create($this->conn, $groupname1);
    }

    /**
     * Remove any data created by the tests.
     *
     * @return null
     */
    public function tearDown()
    {
        $users = RestAuthUser::getAll($this->conn);
        foreach ($users as $user) {
            $user->remove();
        }
        $groups = RestAuthGroup::getAll($this->conn);
        foreach ($groups as $group) {
            $group->remove();
        }
    }

    /**
     * Try adding a group.
     *
     * @return null
     */
    public function testAddGroup()
    {
        global $user, $group, $groupname1;
        $user->addGroup($groupname1);
        $this->assertEquals(array($group), $user->getGroups());
        $this->assertTrue($user->inGroup($groupname1));
    }

    /**
     * Test if a user is in a group.
     *
     * @return null
     */
    public function testInGroup()
    {
        global $user, $group, $groupname1;
        $this->assertFalse($user->inGroup($groupname1));
        $user->addGroup($groupname1);
        $this->assertEquals(array($group), $user->getGroups());
        $this->assertTrue($user->inGroup($groupname1));
    }

    /**
     * Try removing a group.
     *
     * @return null
     */
    public function testRemoveGroup()
    {
        global $user, $group, $groupname1;

        $this->assertFalse($user->inGroup($groupname1));
        $this->assertEquals(array(), $user->getGroups());

        $user->addGroup($groupname1);
        $this->assertTrue($user->inGroup($groupname1));
        $this->assertEquals(array($group), $user->getGroups());

        $user->removeGroup($groupname1);
        $this->assertFalse($user->inGroup($groupname1));
        $this->assertEquals(array(), $user->getGroups());
    }

    /**
     * Try getting the groups of an invalid user.
     *
     * @return null
     */
    public function testGetGroupsInvalidUser()
    {
        $user = new RestAuthUser($this->conn, "foobar");
        try {
            $user->getGroups();
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }
}

?>
