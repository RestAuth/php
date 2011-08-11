<?php

require_once 'PHPUnit/Framework.php';
require_once 'RestAuth/restauth.php';

class GroupTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        global $conn, $username1, $username2, $groupname1, $groupname2;
        global $user1, $group1, $group2;

        $host = 'http://[::1]:8000';
        $user = 'vowi';
        $pass = 'vowi';
        $conn = RestAuthConnection::getConnection($host, $user, $pass);

        $users = RestAuthUser::getAll($conn);
        if (count($users)) {
            throw new Exception("Found " . count($users) . " left over users.");
        }
        $groups = RestAuthGroup::getAll($conn);
        if (count($groups)) {
            throw new Exception("Found " . count($groups) . " left over users.");
        }

        $user1 = RestAuthUser::create($conn, $username1, "foobar");
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

    public function testCreateGroup()
    {
        global $conn, $group1, $groupname1;

        $group1 = RestAuthGroup::create($conn, $groupname1);
        $this->assertEquals(array($group1), RestAuthGroup::getAll($conn));
        $this->assertEquals($group1, RestauthGroup::get($conn, $groupname1));
    }

    public function testCreateGroupTwice()
    {
        global $conn, $group1, $groupname1;
        
        $group1 = RestAuthGroup::create($conn, $groupname1);
        try {
            RestAuthGroup::create($conn, $groupname1);
            $this->fail();
        } catch (RestAuthGroupExists $e) {
            $this->assertEquals(array($group1), RestAuthGroup::getAll($conn));
        }    
    }

    public function testCreateInvalidGroup()
    {
        global $conn;

        try {
            RestAuthGroup::create($conn, "foo/bar");
            $this->fail();
        } catch (RestAuthPreconditionFailed $e) {
            $this->assertEquals(array(), RestAuthGroup::getAll($conn));
        }
    }

    public function testAddUser()
    {
        global $conn, $user1, $username2, $username3, $groupname1;
        global $groupname2;
        $group1 = RestAuthGroup::create($conn, $groupname1);
        $group2 = RestAuthGroup::create($conn, $groupname2);
        $user2 = RestAuthUser::create($conn, $username2, "foobar");
        $user3 = RestAuthUser::create($conn, $username3, "foobar");

        $this->assertEquals(
            array($group1, $group2), RestAuthGroup::getAll($conn)
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

    public function testAddInvalidUser()
    {
        global $conn, $username3, $groupname1;
        $group = RestAuthGroup::create($conn, $groupname1);

        try {
            $group->addUser($username3);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
            $this->assertEquals(array(), $group->getMembers());
        }
    }

    public function testAddUserToInvalidGroup()
    {
        global $conn, $user1, $groupname1;
        $group = new RestAuthGroup($conn, $groupname1);

        try {
            $group->addUser($user1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
            $this->assertEquals(array(), RestAuthGroup::getAll($conn));
        }
    }

    public function testIsMemberInvalidUser()
    {
        global $conn, $username3, $groupname1;
        $group = RestAuthGroup::create($conn, $groupname1);

        $this->assertFalse($group->isMember($username3));
    }

    public function testIsMemberInvalidGroup()
    {
        global $conn, $user1, $groupname1;

        $group = new RestAuthGroup($conn, $groupname1);
        try {
            $group->isMember($user1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    public function testRemoveUser()
    {
        global $conn, $user1, $groupname1;
        $group = RestAuthGroup::create($conn, $groupname1);
        $group->addUser($user1);
        $this->assertEquals(array($user1), $group->getMembers());

        $group->removeUser($user1);
        $this->assertEquals(array(), $group->getMembers());
        $this->assertFalse($group->isMember($user1));
    }

    public function testRemoveUserNotMember()
    {
        global $conn, $user1, $groupname1;
        $group = RestAuthGroup::create($conn, $groupname1);
        $this->assertFalse($group->isMember($user1));

        try {
            $group->removeUser($user1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }

    public function testRemoveInvalidUser()
    {
        global $conn, $username3, $groupname1;
        $group = RestAuthGroup::create($conn, $groupname1);
        
        try {
            $group->removeUser($username3);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }

    public function testRemoveUserFromInvalidGroup()
    {
        global $conn, $user1, $groupname1;
        $group = new RestAuthGroup($conn, $groupname1);
        
        try {
            $group->removeUser($user1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    public function testRemoveInvalidUserFromInvalidGroup()
    {
        global $conn, $username3, $groupname1;
        $group = new RestAuthGroup($conn, $groupname1);
        $user = new RestAuthUser($conn, $username3);
        
        try {
            $group->removeUser($user);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    public function testRemoveGroup()
    {
        global $conn, $groupname1;
        $group = RestAuthGroup::create($conn, $groupname1);
        $group->remove();
        $this->assertEquals(array(), RestAuthGroup::getAll($conn));
    }

    public function testRemoveInvalidGroup()
    {
        global $conn, $groupname1;
        
        $group = new RestAuthGroup($conn, $groupname1);
        try {
            $group->remove();
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    public function testGetInvalidGroup()
    {
        global $conn, $groupname1;

        try {
            RestAuthGroup::get($conn, $groupname1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    public function testGetMembersInvalidGroup()
    {
        global $conn, $groupname1;

        $group = new RestAuthGroup($conn, $groupname1);
        try {
            $group->getMembers();
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    public function testGetGroupsForUser()
    {
        global $username1;

        $conn = RestAuthConnection::getConnection();
        $this->assertEquals(array(), RestAuthGroup::getAll($conn, $username1));
    }
}


?>
