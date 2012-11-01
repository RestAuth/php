<?php
/**
 * This file does some metagroup tests.
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
 * @link       https://php.restauth.net
 */

require_once 'PHPUnit/Autoload.php';
require_once 'RestAuth/restauth.php';

// variables are defined in UserTest.php

/**
 * Do some metagroup tests.
 *
 * @category   Testing
 * @package    RestAuth
 * @subpackage Testing
 * @author     Mathias Ertl <mati@restauth.net>
 * @copyright  2010-2011 Mathias Ertl
 * @license    http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version    Release: @package_version@
 * @link       https://php.restauth.net
 */
class MetaGroupTest extends PHPUnit_Framework_TestCase
{
    /**
     * Set up the data for the tests.
     *
     * @return null
     */
    public function setUp()
    {
        global $username1, $username2, $username3, $username4;
        global $groupname1, $groupname2, $groupname3, $groupname4;
        global $user1, $user2, $user3, $user4;
        global $group1, $group2, $group3, $group4;

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
            throw new Exception(
                "Found " . count($groups) . " left over groups."
            );
        }

        $user1 = RestAuthUser::create($this->conn, $username1, "foobar");
        $user2 = RestAuthUser::create($this->conn, $username2, "blabla");
        $user3 = RestAuthUser::create($this->conn, $username3, "labalaba");
        $user4 = RestAuthUser::create($this->conn, $username4, "labalaba");

        $group1 = RestAuthGroup::create($this->conn, $groupname1);
        $group2 = RestAuthGroup::create($this->conn, $groupname2);
        $group3 = RestAuthGroup::create($this->conn, $groupname3);
        $group4 = RestAuthGroup::create($this->conn, $groupname4);
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
     * Test some simple inheritance.
     *
     * @return null
     */
    public function testSimpleInheritance()
    {
        global $group1, $group2, $group3, $group4;
        global $user1, $user2, $user3, $user4;

        // add some memberships:
        $group1->addUser($user1);
        $group1->addUser($user2);
        $group2->addUser($user2); // user2 in both groups!
        $group2->addUser($user3);
        $group2->addUser($user4);

        // verify initial state
        $testArray = $group1->getMembers();
        usort($testArray, array("RestAuthUser", "cmp"));
        $this->assertEquals(array($user1, $user2), $testArray);
        $testArray = $group2->getMembers();
        usort($testArray, array("RestAuthUser", "cmp"));
        $this->assertEquals(array($user2, $user3, $user4), $testArray);

        // make group2 a subgroup of group1
        $group1->addGroup($group2);

        // verify that group1 hasn't changed
        $testArray = $group1->getMembers();
        usort($testArray, array("RestAuthUser", "cmp"));
        $this->assertEquals(array($user1, $user2), $testArray);
        $this->assertTrue($group1->isMember($user1));
        $this->assertTrue($group1->isMember($user2));
        $this->assertFalse($group1->isMember($user3));
        $this->assertFalse($group1->isMember($user4));

        // verify that group2 now inherits memberships from group1:
        $testArray = $group2->getMembers();
        usort($testArray, array("RestAuthUser", "cmp"));
        $this->assertEquals(array($user1, $user2, $user3, $user4), $testArray);
        $this->assertTrue($group2->isMember($user1));
        $this->assertTrue($group2->isMember($user2));
        $this->assertTrue($group2->isMember($user3));
        $this->assertTrue($group2->isMember($user4));

        // verify subgroups:
        $this->assertEquals(array($group2), $group1->getGroups());
        $this->assertEquals(array(), $group2->getGroups());
    }

    /**
     * Try adding a non-existing group to a group.
     *
     * @return null
     */
    public function testAddInvalidGroup()
    {
        global $group1, $groupname5;

        try {
            $group1->addGroup($groupname5);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
            $this->assertEquals(array(), $group1->getGroups());
        }
    }

    /**
     * Try adding a group to a non-existing group.
     *
     * @return null
     */
    public function testAddGroupToInvalidGroup()
    {
        global $group1, $groupname5;
        $group5 = new RestAuthGroup($this->conn, $groupname5);
        try {
            $group5->addGroup($group1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
            $this->assertEquals(array(), $group1->getGroups());
        }
    }

    /**
     * Try removing a group from a group.
     *
     * @return null
     */
    public function testRemoveGroup()
    {
        global $group1, $group2, $user1, $user2;

        $group1->addUser($user1);
        $group2->addUser($user2);

        // verify initial state:
        $this->assertEquals(array($user1), $group1->getMembers());
        $this->assertEquals(array($user2), $group2->getMembers());
        $this->assertTrue($group1->isMember($user1));
        $this->assertTrue($group2->isMember($user2));
        $this->assertFalse($group1->isMember($user2));
        $this->assertFalse($group2->isMember($user1));

        // create group-relationship
        $group1->addGroup($group2);

        // verify state now:
        $this->assertEquals(array($user1), $group1->getMembers());
        $testArray = $group2->getMembers();
        usort($testArray, array("RestAuthUser", "cmp"));
        $this->assertEquals(array($user1, $user2), $testArray);
        $this->assertTrue($group1->isMember($user1));
        $this->assertTrue($group2->isMember($user1));
        $this->assertTrue($group2->isMember($user2));
        $this->assertFalse($group1->isMember($user2));

        $group1->removeGroup($group2);

        // verify inital state:
        $this->assertEquals(array($user1), $group1->getMembers());
        $this->assertEquals(array($user2), $group2->getMembers());
        $this->assertTrue($group1->isMember($user1));
        $this->assertTrue($group2->isMember($user2));
        $this->assertFalse($group1->isMember($user2));
        $this->assertFalse($group2->isMember($user1));
    }

    /**
     * Try removing a group that is not a subgroup.
     *
     * @return null
     */
    public function testRemoveGroupNotMember()
    {
        global $group1, $group2;

        try {
            $group1->removeGroup($group2);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    /**
     * Try removing an invalid subgroup.
     *
     * @return null
     */
    public function testRemoveInvalidGroup()
    {
        global $group1, $groupname5;

        try {
            $group1->removeGroup($groupname5);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    /**
     * Try getting subgroups of a non-existing group.
     *
     * @return null
     */
    public function testGetGroupsInvalidGroup()
    {
        global $groupname5;
        $group5 = new RestAuthGroup($this->conn, $groupname5);

        try {
            $group5->getGroups();
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }
}

?>
