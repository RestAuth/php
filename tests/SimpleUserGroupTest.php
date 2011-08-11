<?php

require_once 'RestAuth/restauth.php';

// variables are defined in UserTest.php
 
class SimpleUserGroupTest extends PHPUnit_Framework_TestCase
{
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

    public function testAddGroup()
    {
        global $user, $group, $groupname1;
        $user->addGroup($groupname1);
        $this->assertEquals(array($group), $user->getGroups());
        $this->assertTrue($user->inGroup($groupname1));
    }

    public function testInGroup()
    {
        global $user, $group, $groupname1;
        $this->assertFalse($user->inGroup($groupname1));
        $user->addGroup($groupname1);
        $this->assertEquals(array($group), $user->getGroups());
        $this->assertTrue($user->inGroup($groupname1));
    }

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
