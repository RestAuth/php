<?php

require_once('PHPUnit/Framework.php');
require_once('RestAuth/restauth.php');

// variables are defined in UserTest.php

class PropertyTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        global $username1, $user, $password1, $conn;

        $users = RestAuthUser::get_all($conn);
        if (count($users)) {
            throw new Exception("Found " . count($users) . " left over users.");
        }

        $user = RestAuthUser::create($conn, $username1, $password1);
    }
    public function tearDown()
    {
        global $conn;

        $users = RestAuthUser::get_all($conn);
        foreach ($users as $user) {
            $user->remove();
        }
    }

    public function testCreateProperty()
    {
        global $conn, $user, $propKey, $propVal;

        $user->createProperty($propKey, $propVal);
        $this->assertEquals(array($propKey => $propVal),
            $user->getProperties());
        $this->assertEquals($propVal, 
            $user->getProperty($propKey));
    }

    public function testCreatePropertyTwice()
    {
        global $conn, $user, $propKey, $propVal;

        $user->createProperty($propKey, $propVal);
        try {
            $user->createProperty($propKey, $propVal . " new");
            $this->fail();
        } catch (RestAuthPropertyExists $e) {
            $this->assertEquals(array($propKey => $propVal),
                $user->getProperties());
            $this->assertEquals($propVal, 
                $user->getProperty($propKey));
        }
    }

    public function testCreatePropertyWithInvalidUser()
    {
        global $conn, $user, $propKey, $propVal;
        $username = "invalid name";

        $invalidUser = new RestAuthUser($conn, $username);
        try {
            $invalidUser->createProperty($propKey, $propVal);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
            $this->assertEquals(array($user), 
                RestAuthUser::get_all($conn));
        }
    }

    public function testSetProperty()
    {
        global $conn, $user, $propKey, $propVal;

        $this->assertNull($user->setProperty($propKey, $propVal));
        $this->assertEquals(array($propKey => $propVal),
            $user->getProperties());
        $this->assertEquals($propVal, 
            $user->getProperty($propKey));
    }

    public function testSetPropertyTwice()
    {
        global $conn, $user, $propKey, $propVal;
        $newVal = "foobar";

        $this->assertNull($user->setProperty($propKey, $propVal));
        $this->assertEquals(array($propKey => $propVal),
            $user->getProperties());
        $this->assertEquals($propVal, 
            $user->getProperty($propKey));

        $this->assertEquals($propVal, 
            $user->setProperty($propKey, $newVal));
        $this->assertEquals(array($propKey => $newVal),
            $user->getProperties());
        $this->assertEquals($newVal, 
            $user->getProperty($propKey));
    }

    public function testSetPropertyWithInvalidUser()
    {
        global $conn, $user, $propKey, $propVal;
        $username = "invalid name";

        $invalidUser = new RestAuthUser($conn, $username);
        try {
            $invalidUser->setProperty($propKey, $propVal);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
            $this->assertEquals(array($user), 
                RestAuthUser::get_all($conn));
        }
    }

    public function testRemoveProperty()
    {
        global $conn, $user, $propKey, $propVal;
        
        $this->assertNull($user->setProperty($propKey, $propVal));
        $this->assertEquals(array($propKey => $propVal),
            $user->getProperties());
        $this->assertEquals($propVal, 
            $user->getProperty($propKey));

        $user->removeProperty($propKey);
        $this->assertEquals(array(), $user->getProperties());
    }

    public function testRemoveInvalidProperty()
    {
        global $conn, $user, $propKey, $propVal;
        $user->createProperty($propKey, $propVal);

        $wrongKey = $propKey . " foo";

        try {
            $user->removeProperty($wrongKey);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("property", $e->getType());
            $this->assertEquals(array($propKey => $propVal),
                $user->getProperties());
            $this->assertEquals($propVal, 
                $user->getProperty($propKey));
        }
    }

    public function testRemovePropertyWithInvalidUser()
    {
        global $conn, $user, $propKey, $propVal;
        $user->setProperty($propKey, $propVal);
        $username = "invalid name";

        $invalidUser = new RestAuthUser($conn, $username);
        try {
            $invalidUser->removeProperty($propKey);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());

            $this->assertEquals(array($propKey => $propVal),
                $user->getProperties());
            $this->assertEquals($propVal, 
                $user->getProperty($propKey));
        }
    }

    public function testGetInvalidProperty()
    {
        global $conn, $user, $propKey, $propVal;

        try {
            $user->getProperty($propKey); 
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("property", $e->getType());
        }
    }

    public function testGetPropertyInvalidUser()
    {
        global $conn, $user, $propKey, $propVal;
        $username = "invalid name";

        $invalidUser = new RestAuthUser($conn, $username);
        try {
            $invalidUser->getProperty($propKey); 
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }

    public function testGetPropertiesInvalidUser()
    {
        global $conn, $user, $propKey, $propVal;
        $username = "invalid name";

        $invalidUser = new RestAuthUser($conn, $username);
        try {
            $invalidUser->getProperties($propKey); 
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }
}

?>
