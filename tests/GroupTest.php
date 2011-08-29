<?php
/**
 * This file does some basic group tests.
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
 * @category   Testing
 * @package    RestAuth
 * @subpackage Testing
 * @author     Mathias Ertl <mati@restauth.net>
 * @copyright  2010-2011 Mathias Ertl
 * @license    http://www.gnu.org/licenses/lgpl.html  GNU LESSER GENERAL PUBLIC LICENSE
 * @version    0.0
 * @link       https://php.restauth.net
 */

require_once 'PHPUnit/Framework.php';
require_once 'RestAuth/restauth.php';

/**
 * Do some basic group tests.
 *
 * @category   Testing
 * @package    RestAuth
 * @subpackage Testing
 * @author     Mathias Ertl <mati@restauth.net>
 * @copyright  2010-2011 Mathias Ertl
 * @license    http://www.gnu.org/licenses/lgpl.html  GNU LESSER GENERAL PUBLIC LICENSE
 * @version    Release: @package_version@
 * @link       https://php.restauth.net
 */
class GroupTest extends PHPUnit_Framework_TestCase
{
    /**
     * Set up the data for the tests.
     *
     * @return null
     */
    public function setUp()
    {
        global $username1, $username2, $groupname1, $groupname2;
        global $user1, $group1, $group2;

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

        $user1 = RestAuthUser::create($this->conn, $username1, "foobar");
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
     * Basic test creating a group.
     *
     * @return null
     */
    public function testCreateGroup()
    {
        global $group1, $groupname1;

        $group1 = RestAuthGroup::create($this->conn, $groupname1);
        $this->assertEquals(array($group1), RestAuthGroup::getAll($this->conn));
        $this->assertEquals(
            $group1, RestauthGroup::get($this->conn, $groupname1)
        );
    }

    /**
     * Try creating a group twice.
     *
     * @return null
     */
    public function testCreateGroupTwice()
    {
        global $group1, $groupname1;
        
        $group1 = RestAuthGroup::create($this->conn, $groupname1);
        try {
            RestAuthGroup::create($this->conn, $groupname1);
            $this->fail();
        } catch (RestAuthGroupExists $e) {
            $this->assertEquals(array($group1), RestAuthGroup::getAll($this->conn));
        }    
    }

    /**
     * Try creating a group with an invalid entity name (containing a '/').
     *
     * @return null
     */
    public function testCreateInvalidGroup()
    {
        try {
            RestAuthGroup::create($this->conn, "foo/bar");
            $this->fail();
        } catch (RestAuthPreconditionFailed $e) {
            $this->assertEquals(array(), RestAuthGroup::getAll($this->conn));
        }
    }

    /**
     * Add a user to a group.
     *
     * @return null
     */
    public function testAddUser()
    {
        global $user1, $username2, $username3, $groupname1;
        global $groupname2;
        $group1 = RestAuthGroup::create($this->conn, $groupname1);
        $group2 = RestAuthGroup::create($this->conn, $groupname2);
        $user2 = RestAuthUser::create($this->conn, $username2, "foobar");
        $user3 = RestAuthUser::create($this->conn, $username3, "foobar");

        $this->assertEquals(
            array($group1, $group2), RestAuthGroup::getAll($this->conn)
        );

        $group1->addUser($user1);
        $group2->addUser($user2);
        $group2->addUser($user3);

        $this->assertEquals(array($user1), $group1->getMembers());
        $testArray = $group2->getMembers();
        usort($testArray, array("RestAuthUser", "cmp"));
        $this->assertEquals(array($user2, $user3), $testArray);

        $this->assertTrue($group1->isMember($user1));
        $this->assertTrue($group2->isMember($user2));
        $this->assertTrue($group2->isMember($user3));
        $this->assertFalse($group2->isMember($user1));
        $this->assertFalse($group1->isMember($user2));
        $this->assertFalse($group1->isMember($user3));
    }

    /**
     * Try adding a non-existing user to a group.
     *
     * @return null
     */
    public function testAddInvalidUser()
    {
        global $username3, $groupname1;
        $group = RestAuthGroup::create($this->conn, $groupname1);

        try {
            $group->addUser($username3);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
            $this->assertEquals(array(), $group->getMembers());
        }
    }

    /**
     * Try adding a user to an invalid group.
     *
     * @return null
     */
    public function testAddUserToInvalidGroup()
    {
        global $user1, $groupname1;
        $group = new RestAuthGroup($this->conn, $groupname1);

        try {
            $group->addUser($user1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
            $this->assertEquals(array(), RestAuthGroup::getAll($this->conn));
        }
    }

    /**
     * Try testing the membership of a non-existing user.
     *
     * @return null
     */
    public function testIsMemberInvalidUser()
    {
        global $username3, $groupname1;
        $group = RestAuthGroup::create($this->conn, $groupname1);

        $this->assertFalse($group->isMember($username3));
    }

    /**
     * Try testing the membership of a user in a non-existing group.
     *
     * @return null
     */
    public function testIsMemberInvalidGroup()
    {
        global $user1, $groupname1;

        $group = new RestAuthGroup($this->conn, $groupname1);
        try {
            $group->isMember($user1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    /**
     * Try removing a user from a group.
     *
     * @return null
     */
    public function testRemoveUser()
    {
        global $user1, $groupname1;
        $group = RestAuthGroup::create($this->conn, $groupname1);
        $group->addUser($user1);
        $this->assertEquals(array($user1), $group->getMembers());

        $group->removeUser($user1);
        $this->assertEquals(array(), $group->getMembers());
        $this->assertFalse($group->isMember($user1));
    }

    /**
     * Try removing a user from a group where he/she is not a member.
     *
     * @return null
     */
    public function testRemoveUserNotMember()
    {
        global $user1, $groupname1;
        $group = RestAuthGroup::create($this->conn, $groupname1);
        $this->assertFalse($group->isMember($user1));

        try {
            $group->removeUser($user1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }

    /**
     * Try removing a non-existing user from a group.
     *
     * @return null
     */
    public function testRemoveInvalidUser()
    {
        global $username3, $groupname1;
        $group = RestAuthGroup::create($this->conn, $groupname1);
        
        try {
            $group->removeUser($username3);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }

    /**
     * Try removing a user from a non-existing group.
     *
     * @return null
     */
    public function testRemoveUserFromInvalidGroup()
    {
        global $user1, $groupname1;
        $group = new RestAuthGroup($this->conn, $groupname1);
        
        try {
            $group->removeUser($user1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    /**
     * Try removing a non-existing user from a non-existing group.
     *
     * @return null
     */
    public function testRemoveInvalidUserFromInvalidGroup()
    {
        global $username3, $groupname1;
        $group = new RestAuthGroup($this->conn, $groupname1);
        $user = new RestAuthUser($this->conn, $username3);
        
        try {
            $group->removeUser($user);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    
    /**
     * Try removing a group.
     *
     * @return null
     */
    public function testRemoveGroup()
    {
        global $groupname1;
        $group = RestAuthGroup::create($this->conn, $groupname1);
        $group->remove();
        $this->assertEquals(array(), RestAuthGroup::getAll($this->conn));
    }

    /**
     * Try removing a non-existing group.
     *
     * @return null
     */
    public function testRemoveInvalidGroup()
    {
        global $groupname1;
        
        $group = new RestAuthGroup($this->conn, $groupname1);
        try {
            $group->remove();
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    /**
     * Try getting an invalid-group.
     *
     * @return null
     */
    public function testGetInvalidGroup()
    {
        global $groupname1;

        try {
            RestAuthGroup::get($this->conn, $groupname1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    /**
     * Try getting members of a non-existing group.
     *
     * @return null
     */
    public function testGetMembersInvalidGroup()
    {
        global $groupname1;

        $group = new RestAuthGroup($this->conn, $groupname1);
        try {
            $group->getMembers();
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    /**
     * Try getting groups of a user.
     *
     * @return null
     */
    public function testGetGroupsForUser()
    {
        global $username1;

        $this->conn = RestAuthConnection::getConnection();
        $this->assertEquals(
            array(), RestAuthGroup::getAll($this->conn, $username1)
        );
    }
}


?>
