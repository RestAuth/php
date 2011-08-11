<?php

require_once 'PHPUnit/Framework.php';
require_once 'RestAuth/restauth.php';

class GroupTest extends PHPUnit_Framework_TestCase
{
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

    public function testCreateGroup()
    {
        global $group1, $groupname1;

        $group1 = RestAuthGroup::create($this->conn, $groupname1);
        $this->assertEquals(array($group1), RestAuthGroup::getAll($this->conn));
        $this->assertEquals($group1, RestauthGroup::get(
            $this->conn, $groupname1)
        );
    }

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

    public function testCreateInvalidGroup()
    {
        try {
            RestAuthGroup::create($this->conn, "foo/bar");
            $this->fail();
        } catch (RestAuthPreconditionFailed $e) {
            $this->assertEquals(array(), RestAuthGroup::getAll($this->conn));
        }
    }

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

    public function testIsMemberInvalidUser()
    {
        global $username3, $groupname1;
        $group = RestAuthGroup::create($this->conn, $groupname1);

        $this->assertFalse($group->isMember($username3));
    }

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

    public function testRemoveGroup()
    {
        global $groupname1;
        $group = RestAuthGroup::create($this->conn, $groupname1);
        $group->remove();
        $this->assertEquals(array(), RestAuthGroup::getAll($this->conn));
    }

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

    public function testGetGroupsForUser()
    {
        global $username1;

        $this->conn = RestAuthConnection::getConnection();
        $this->assertEquals(array(), RestAuthGroup::getAll(
            $this->conn, $username1)
        );
    }
}


?>
