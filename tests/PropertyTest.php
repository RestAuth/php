<?php
/**
 * This file does some user property tests.
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
 * Do some user property tests.
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
class PropertyTest extends PHPUnit_Framework_TestCase
{
    /**
     * Set up the data for the tests.
     *
     * @return null
     */
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
    }

    /**
     * Try creating a property.
     *
     * @return null
     */
    public function testCreateProperty()
    {
        global $user, $propKey, $propVal;

        $user->createProperty($propKey, $propVal);
        $this->assertEquals(
            array($propKey => $propVal), $user->getProperties()
        );
        $this->assertEquals($propVal, $user->getProperty($propKey));
    }

    /**
     * Try creating a property twice.
     *
     * @return null
     */
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

    /**
     * Try creating a property of an invalid user.
     *
     * @return null
     */
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
    
    /**
     * Try test-creating an invalid property.
     *
     * @return null
     */
    public function testCreateInvalidProperty()
    {
        global $user, $propVal;
        
        try {
            $user->createProperty("foo:bar", $propVal);
            $this->fail();
        } catch (RestAuthPreconditionFailed $e) {
            $this->assertEquals(array(), $user->getProperties());
        }
    }
    
    /**
     * Test to create a property.
     *
     * @return null
     */
    public function testCreatePropertyTest()
    {
        global $user, $propKey, $propVal;
        
        $this->assertNull($user->createPropertyTest($propKey, $propVal));
        $this->assertEquals(array(), $user->getProperties());
    }
    
    /**
     * Test creating an invalid property.
     * 
     * @return null
     */
    public function testCreateInvalidPropertyTest()
    {
        global $user, $propKey, $propVal;
        $user->createProperty($propKey, $propVal);
        
        // create it again
        try {
            $user->createPropertyTest($propKey, "new value");
            $this->fail();
        } catch(RestAuthPropertyExists $e) {
        }
        $this->assertEquals(
            array($propKey => $propVal), $user->getProperties()
        );
        
        // invalid property name
        try {
            $user->createPropertyTest("foo:bar", $propVal);
            $this->fail();
        } catch (RestAuthPreconditionFailed $e) {
        }
        $this->assertEquals(
            array($propKey => $propVal), $user->getProperties()
        );
        
        // non-existing user
        try {
            $user = new RestAuthUser($this->conn, "wronguser");
            $user->createPropertyTest($propKey, $propVal);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
        }
        
        // invalid username
        try {
            $user = new RestAuthUser($this->conn, "invalid:user");
            $user->createPropertyTest($propKey, $propVal);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }

    /**
     * Try setting a property.
     *
     * @return null
     */
    public function testSetProperty()
    {
        global $user, $propKey, $propVal;

        $this->assertNull($user->setProperty($propKey, $propVal));
        $this->assertEquals(
            array($propKey => $propVal), $user->getProperties()
        );
        $this->assertEquals($propVal, $user->getProperty($propKey));
    }

    /**
     * Try setting a property twice.
     *
     * @return null
     */
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

    /**
     * Try setting a property for an invalid user.
     *
     * @return null
     */
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

    /**
     * Try removing a property.
     *
     * @return null
     */
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

    /**
     * TRy removing a non-existing property.
     *
     * @return null
     */
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

    /**
     * Try removing a property of a non-existing user.
     *
     * @return null
     */
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

    /**
     * Try getting a non-existing property.
     *
     * @return null
     */
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

    /**
     * Try getting a property from a non-existing user.
     *
     * @return null
     */
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

    /**
     * Try getting all properties from a non-existing user.
     *
     * @return null
     */
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
