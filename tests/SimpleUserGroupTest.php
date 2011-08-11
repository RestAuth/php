<?php

require_once('RestAuth/restauth.php');

// variables are defined in UserTest.php
 
class SimpleUserGroupTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        global $conn, $user, $username1, $group, $groupname1;

        $host = 'http://[::1]:8000';
        $user = 'vowi';
        $pass = 'vowi';
        $conn = new RestAuthConnection($host, $user, $pass);

        $users = RestAuthUser::getAll($conn);
        if (count($users)) {
            throw new Exception("Found " . count($users) . " left over users.");
        }
        $groups = RestAuthGroup::getAll($conn);
        if (count($groups)) {
            throw new Exception("Found " . count($groups) . " left over users.");
        }

        $user = RestAuthUser::create($conn, $username1, "foobar");
        $group = RestAuthGroup::create($conn, $groupname1);
    }

    public function tearDown()
    {
        global $conn;

        $users = RestAuthUser::getAll($conn);
        foreach ($users as $user) {
            $user->remove();
        }
        $groups = RestAuthGroup::getAll($conn);
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
        global $conn;
        $user = new RestAuthUser($conn, "foobar");
        try {
            $user->getGroups();
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }
}

?>
