<?php

require_once('RestAuth/restauth.php');

// variables are defined in UserTest.php

class MetaGroupTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        global $conn, $username1, $username2, $username3, $username4;
        global $groupname1, $groupname2, $groupname3, $groupname4;
        global $user1, $user2, $user3, $user4;
        global $group1, $group2, $group3, $group4;

        $host = 'http://[::1]:8000';
        $user = 'vowi';
        $pass = 'vowi';
        $conn = new RestAuthConnection($host, $user, $pass);

        $users = RestAuthUser::get_all($conn);
        if (count($users)) {
            throw new Exception("Found " . count($users) . " left over users.");
        }
        $groups = RestAuthGroup::get_all($conn);
        if (count($groups)) {
            throw new Exception("Found " . count($groups) . " left over users.");
        }

        $user1 = RestAuthUser::create($conn, $username1, "foobar");
        $user2 = RestAuthUser::create($conn, $username2, "blabla");
        $user3 = RestAuthUser::create($conn, $username3, "labalaba");
        $user4 = RestAuthUser::create($conn, $username4, "labalaba");

        $group1 = RestAuthGroup::create($conn, $groupname1);
        $group2 = RestAuthGroup::create($conn, $groupname2);
        $group3 = RestAuthGroup::create($conn, $groupname3);
        $group4 = RestAuthGroup::create($conn, $groupname4);
    }

    public function tearDown()
    {
        global $conn;

        $users = RestAuthUser::get_all($conn);
        foreach ($users as $user) {
            $user->remove();
        }
        $groups = RestAuthGroup::get_all($conn);
        foreach ($groups as $group) {
            $group->remove();
        }
    }

    public function testSimpleInheritance()
    {
        global $conn, $group1, $group2, $group3, $group4;
        global $user1, $user2, $user3, $user4;

        // add some memberships:
        $group1->add_user($user1);
        $group1->add_user($user2);
        $group2->add_user($user2); // user2 in both groups!
        $group2->add_user($user3);
        $group2->add_user($user4);

        // verify initial state
        $testArray = $group1->get_members();
        usort($testArray, array("RestAuthUser", "cmp"));
        $this->assertEquals(array($user1, $user2),
            $testArray);
        $testArray = $group2->get_members();
        usort($testArray, array("RestAuthUser", "cmp"));
        $this->assertEquals(array($user2, $user3, $user4),
            $testArray);

        // make group2 a subgroup of group1
        $group1->addGroup($group2);

        // verify that group1 hasn't changed
        $testArray = $group1->get_members();
        usort($testArray, array("RestAuthUser", "cmp"));
        $this->assertEquals(array($user1, $user2),
            $testArray);
        $this->assertTrue($group1->is_member($user1));
        $this->assertTrue($group1->is_member($user2));
        $this->assertFalse($group1->is_member($user3));
        $this->assertFalse($group1->is_member($user4));

        // verify that group2 now inherits memberships from group1:
        $testArray = $group2->get_members();
        usort($testArray, array("RestAuthUser", "cmp"));
        $this->assertEquals(array($user1, $user2, $user3, $user4),
            $testArray);
        $this->assertTrue($group2->is_member($user1));
        $this->assertTrue($group2->is_member($user2));
        $this->assertTrue($group2->is_member($user3));
        $this->assertTrue($group2->is_member($user4));

        // verify subgroups:
        $this->assertEquals(array($group2), $group1->getGroups());
        $this->assertEquals(array(), $group2->getGroups());
    }

    public function testAddInvalidGroup()
    {
        global $conn, $group1, $groupname5;

        try {
            $group1->addGroup($groupname5);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
            $this->assertEquals(array(), $group1->getGroups());
        }
    }

    public function testAddGroupToInvalidGroup()
    {
        global $conn, $group1, $groupname5;
        $group5 = new RestAuthGroup($conn, $groupname5);
        try {
            $group5->addGroup($group1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
            $this->assertEquals(array(), $group1->getGroups());
        }
    }

    public function testRemoveGroup()
    {
        global $conn, $group1, $group2, $user1, $user2;

        $group1->add_user($user1);
        $group2->add_user($user2);
        
        // verify initial state:
        $this->assertEquals(array($user1), $group1->get_members());
        $this->assertEquals(array($user2), $group2->get_members());
        $this->assertTrue($group1->is_member($user1));
        $this->assertTrue($group2->is_member($user2));
        $this->assertFalse($group1->is_member($user2));
        $this->assertFalse($group2->is_member($user1));

        // create group-relationship
        $group1->addGroup($group2);

        // verify state now:
        $this->assertEquals(array($user1), $group1->get_members());
        $testArray = $group2->get_members();
        usort($testArray, array("RestAuthUser", "cmp"));
        $this->assertEquals(array($user1, $user2), 
            $testArray);
        $this->assertTrue($group1->is_member($user1));
        $this->assertTrue($group2->is_member($user1));
        $this->assertTrue($group2->is_member($user2));
        $this->assertFalse($group1->is_member($user2));

        $group1->removeGroup($group2);

        // verify inital state:
        $this->assertEquals(array($user1), $group1->get_members());
        $this->assertEquals(array($user2), $group2->get_members());
        $this->assertTrue($group1->is_member($user1));
        $this->assertTrue($group2->is_member($user2));
        $this->assertFalse($group1->is_member($user2));
        $this->assertFalse($group2->is_member($user1));
    }

    public function testRemoveGroupNotMember()
    {
        global $conn, $group1, $group2;
        
        try {
            $group1->removeGroup($group2);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    public function testRemoveInvalidGroup()
    {
        global $conn, $group1, $groupname5;

        try {
            $group1->removeGroup($groupname5);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }

    public function testGetGroupsInvalidGroup()
    {
        global $conn, $groupname5;
        $group5 = new RestAuthGroup($conn, $groupname5);

        try {
            $group5->getGroups();
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("group", $e->getType());
        }
    }
}

?>
