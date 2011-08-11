<?php

require_once 'PHPUnit/Framework.php';
require_once 'RestAuth/restauth.php';

// variables are defined in UserTest.php

class PropertyTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        global $username1, $user, $password1;
        
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $this->conn = RestAuthConnection::getConnection(
            $RestAuthHost, $RestAuthUser, $RestAuthPass
        );

        $users = RestAuthUser::getAll($this->conn);
        if (count($users)) {
            throw new Exception("Found " . count($users) . " left over users.");
        }

        $user = RestAuthUser::create($this->conn, $username1, $password1);
    }
    public function tearDown()
    {
        $users = RestAuthUser::getAll($this->conn);
        foreach ($users as $user) {
            $user->remove();
        }
    }

    public function testCreateProperty()
    {
        global $user, $propKey, $propVal;

        $user->createProperty($propKey, $propVal);
        $this->assertEquals(
            array($propKey => $propVal), $user->getProperties()
        );
        $this->assertEquals($propVal, $user->getProperty($propKey));
    }

    public function testCreatePropertyTwice()
    {
        global $user, $propKey, $propVal;

        $user->createProperty($propKey, $propVal);
        try {
            $user->createProperty($propKey, $propVal . " new");
            $this->fail();
        } catch (RestAuthPropertyExists $e) {
            $this->assertEquals(
                array($propKey => $propVal), $user->getProperties()
            );
            $this->assertEquals($propVal, $user->getProperty($propKey));
        }
    }

    public function testCreatePropertyWithInvalidUser()
    {
        global $user, $propKey, $propVal;
        $username = "invalid name";

        $invalidUser = new RestAuthUser($this->conn, $username);
        try {
            $invalidUser->createProperty($propKey, $propVal);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
            $this->assertEquals(
                array($user), RestAuthUser::getAll($this->conn)
            );
        }
    }

    public function testSetProperty()
    {
        global $user, $propKey, $propVal;

        $this->assertNull($user->setProperty($propKey, $propVal));
        $this->assertEquals(
            array($propKey => $propVal), $user->getProperties()
        );
        $this->assertEquals($propVal, $user->getProperty($propKey));
    }

    public function testSetPropertyTwice()
    {
        global $user, $propKey, $propVal;
        $newVal = "foobar";

        $this->assertNull($user->setProperty($propKey, $propVal));
        $this->assertEquals(
            array($propKey => $propVal), $user->getProperties()
        );
        $this->assertEquals($propVal, $user->getProperty($propKey));

        $this->assertEquals($propVal, $user->setProperty($propKey, $newVal));
        $this->assertEquals(array($propKey => $newVal), $user->getProperties());
        $this->assertEquals($newVal, $user->getProperty($propKey));
    }

    public function testSetPropertyWithInvalidUser()
    {
        global $user, $propKey, $propVal;
        $username = "invalid name";

        $invalidUser = new RestAuthUser($this->conn, $username);
        try {
            $invalidUser->setProperty($propKey, $propVal);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
            $this->assertEquals(array($user), RestAuthUser::getAll($this->conn));
        }
    }

    public function testRemoveProperty()
    {
        global $user, $propKey, $propVal;
        
        $this->assertNull($user->setProperty($propKey, $propVal));
        $this->assertEquals(
            array($propKey => $propVal), $user->getProperties()
        );
        $this->assertEquals($propVal, $user->getProperty($propKey));

        $user->removeProperty($propKey);
        $this->assertEquals(array(), $user->getProperties());
    }

    public function testRemoveInvalidProperty()
    {
        global $user, $propKey, $propVal;
        $user->createProperty($propKey, $propVal);

        $wrongKey = $propKey . " foo";

        try {
            $user->removeProperty($wrongKey);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("property", $e->getType());
            $this->assertEquals(
                array($propKey => $propVal), $user->getProperties()
            );
            $this->assertEquals($propVal, $user->getProperty($propKey));
        }
    }

    public function testRemovePropertyWithInvalidUser()
    {
        global $user, $propKey, $propVal;
        $user->setProperty($propKey, $propVal);
        $username = "invalid name";

        $invalidUser = new RestAuthUser($this->conn, $username);
        try {
            $invalidUser->removeProperty($propKey);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());

            $this->assertEquals(
                array($propKey => $propVal), $user->getProperties()
            );
            $this->assertEquals($propVal, $user->getProperty($propKey));
        }
    }

    public function testGetInvalidProperty()
    {
        global $user, $propKey, $propVal;

        try {
            $user->getProperty($propKey); 
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("property", $e->getType());
        }
    }

    public function testGetPropertyInvalidUser()
    {
        global $user, $propKey, $propVal;
        $username = "invalid name";

        $invalidUser = new RestAuthUser($this->conn, $username);
        try {
            $invalidUser->getProperty($propKey); 
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }

    public function testGetPropertiesInvalidUser()
    {
        global $user, $propKey, $propVal;
        $username = "invalid name";

        $invalidUser = new RestAuthUser($this->conn, $username);
        try {
            $invalidUser->getProperties($propKey); 
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }
}

?>
