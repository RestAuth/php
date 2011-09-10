<?php
/**
 * This file does some basic user tests.
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
 * @version    0.5.0
 * @link       https://php.restauth.net
 */

require_once 'PHPUnit/Framework.php';
require_once 'RestAuth/restauth.php';

// setup the connection:
$RestAuthHost = 'http://[::1]:8000';
$RestAuthUser = 'vowi';
$RestAuthPass = 'vowi';

// various test data. 
$username1 = "user ɨʄɔ"; // IPA (\u0268\u0284\u0254)
$username2 = "user θσξ"; // Greek (\u03b8\u03c3\u03be)
$username3 = "user わたし"; // Hiranga (\u308f\u305f\u3057)
$username4 = "user 조선글"; // Chosongul (North Korea) (\uc870\uc120\uae00)
$username5 = "user 한글"; // Hangul (South Korea) (\ud55c\uae00)

$password1 = 'foo bar';
$password2 = 'bla hugo';

$groupname1 = "group بشك"; // Arabic
$groupname2 = "group 漢字働働"; // Kanji
$groupname3 = "group אָלֶף־בֵּית עִבְרִי"; // hebrew
$groupname4 = "group अऋआऐ";
$groupname5 = "group ӁӜӚ"; // cyrillic

$propKey = "property 漢字"; // traditional chinese
$propVal = "value 汉字"; // simplified chinese

/**
 * Basic user handling tests.
 * 
 * @category   Testing
 * @package    RestAuth
 * @subpackage Testing
 * @author     Mathias Ertl <mati@restauth.net>
 * @copyright  2010-2011 Mathias Ertl
 * @license    http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version    Release: 0.5.0
 * @link       https://php.restauth.net
 */
class UserTest extends PHPUnit_Framework_TestCase
{
    /**
     * Set up the data for the tests.
     *
     * @return null
     */
    public function setUp()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $this->conn = RestAuthConnection::getConnection(
            $RestAuthHost, $RestAuthUser, $RestAuthPass
        );

        $users = RestAuthUser::getAll($this->conn);
        if (count($users)) {
            throw new Exception("Found " . count($users) . " left over users.");
        }
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
     * Try creating a user.
     *
     * @return null
     */
    public function testCreateUser()
    {
        global $username1, $password1;

        $user = RestAuthUser::create($this->conn, $username1, $password1);

        $this->assertEquals(array($user), RestAuthUser::getAll($this->conn));
        $this->assertEquals($user, RestAuthUser::get($this->conn, $username1));
    }

    /**
     * Try creating a user with no password.
     *
     * @return null
     */
    public function testCreateUserNoPassword()
    {
        global $username1, $username2, $username3, $password1;

        $user = RestAuthUser::create($this->conn, $username1);
        $this->assertEquals(array($user), RestAuthUser::getAll($this->conn));
        $this->assertEquals($user, RestAuthUser::get($this->conn, $username1));

        $this->assertFalse($user->verifyPassword($password1));
        $this->assertFalse($user->verifyPassword(''));
        $this->assertFalse($user->verifyPassword(null));

        $user = RestAuthUser::create($this->conn, $username2, '');
        $this->assertEquals($user, RestAuthUser::get($this->conn, $username2));

        $this->assertFalse($user->verifyPassword($password1));
        $this->assertFalse($user->verifyPassword(''));
        $this->assertFalse($user->verifyPassword(null));

        $user = RestAuthUser::create($this->conn, $username3, null);
        $this->assertEquals($user, RestAuthUser::get($this->conn, $username3));

        $this->assertFalse($user->verifyPassword($password1));
        $this->assertFalse($user->verifyPassword(''));
        $this->assertFalse($user->verifyPassword(null));
    }
    
    /**
     * Try creating a user with initial properties.
     *
     * @return null
     */
    public function testCreateUserWithProperties()
    {
        global $username1, $username2, $username3, $username4, $propKey, $propVal;
        
        $user = RestAuthUser::create($this->conn, $username1, null, null);
        $this->assertEquals(array(), $user->getProperties());
        
        $user = RestAuthUser::create($this->conn, $username2, null, array());
        $this->assertEquals(array(), $user->getProperties());
        
        $initProps = array( $propKey => $propVal );
        $user = RestAuthUser::create($this->conn, $username3, null, $initProps);
        $this->assertEquals($initProps, $user->getProperties());
        $this->assertEquals($propVal, $user->getProperty($propKey));
        
        $initProps['foo'] = 'bar';
        ksort($initProps);
        $user = RestAuthUser::create($this->conn, $username4, null, $initProps);
        $all_props = $user->getProperties();
        ksort($all_props);
        $this->assertEquals($initProps, $all_props);
        $this->assertEquals($propVal, $user->getProperty($propKey));
        $this->assertEquals('bar', $user->getProperty('foo'));
    }

    /**
     * Try creating a user with an invalid entity name (containing a slash).
     *
     * @return null
     */
    public function testCreateInvalidUser()
    {
        global $username1, $password1;

        try {
            RestAuthUser::create($this->conn, "foo/bar", "don't care");
            $this->fail();
        } catch (RestAuthPreconditionFailed $e) {
            $this->assertEquals(array(), RestAuthUser::getAll($this->conn));
        }
    }
    
    /**
     * Try creating a user twice.
     *
     * @return null
     */
    public function testCreateUserTwice()
    {
        global $username1, $password1;
        $new_pass = "new " . $password1;

        $user = RestAuthUser::create($this->conn, $username1, $password1);
        $this->assertEquals($user, RestAuthUser::get($this->conn, $username1));

        try {
            RestAuthUser::create($this->conn, $username1, $new_pass);
            $this->fail();
        } catch (RestAuthUserExists $e) {
            $this->assertTrue($user->verifyPassword($password1));
            $this->assertFalse($user->verifyPassword($new_pass));
        }
    }
    
    /**
     * Test for creating users.
     *
     * @return null
     */
    public function testCreateUserTest()
    {
        global $username1, $password1, $propKey, $propVal;
        
        $this->assertTrue(RestAuthUser::createTest($this->conn, $username1));
        $this->assertTrue(
            RestAuthUser::createTest(
                $this->conn, $username1, $password1
            )
        );
        $this->assertTrue(
            RestAuthUser::createTest(
                $this->conn, $username1, null, array($propKey, $propVal)
            )
        );
        $this->assertTrue(
            RestAuthUser::createTest(
                $this->conn, $username1, $password1, array($propKey, $propVal)
            )
        );
    }
    
    /**
     * Test for creating invalid users.
     *
     * @return null
     */
    public function testCreateInvalidUserTest()
    {
        global $username1, $password1;
        
        // username too short:
        $this->assertFalse(RestAuthUser::createTest($this->conn, 'a'));
        // username invalid:
        $this->assertFalse(RestAuthUser::createTest($this->conn, 'user:name'));
        // password too short
        $this->assertFalse(
            RestAuthUser::createTest(
                $this->conn, $username1, 'a'
            )
        );
        
        // existing user:
        $user = RestAuthUser::create($this->conn, $username1, $password1);
        $this->assertFalse(
            RestAuthUser::createTest(
                $this->conn, $username1, "new password"
            )
        );
        $this->assertTrue($user->verifyPassword($password1));
    }
    
    /**
     * Try verifying the password.
     *
     * @return null
     */
    public function testVerifyPassword()
    {
        global $username1, $password1;
        $user = RestAuthUser::create($this->conn, $username1, $password1);

        $this->assertTrue($user->verifyPassword($password1));
        $this->assertFalse($user->verifyPassword("something else"));
    }

    /**
     * Try verifying the password of a non-existing user.
     *
     * @return null
     */
    public function testVerifyPasswordInvalidUser()
    {
        global $username1, $password1;
        
        $user = new RestAuthUser($this->conn, $username1);

        $this->assertFalse($user->verifyPassword("foobar"));
    }

    /**
     * Try setting a new password.
     *
     * @return null
     */
    public function testSetPassword()
    {
        global $username1, $password1;
        $new_pass = "something else";


        $user = RestAuthUser::create($this->conn, $username1, $password1);
        $this->assertTrue($user->verifyPassword($password1));
        $this->assertFalse($user->verifyPassword($new_pass));

        $user->setPassword($new_pass);

        $this->assertFalse($user->verifyPassword($password1));
        $this->assertTrue($user->verifyPassword($new_pass));
    }

    /**
     * Try disabling a password.
     *
     * @return null
     */
    public function testDisablePassword()
    {
        global $username1, $password1;
        $user = RestAuthUser::create($this->conn, $username1, $password1);
        $this->assertTrue($user->verifyPassword($password1));
        $this->assertFalse($user->verifyPassword(''));
        $this->assertFalse($user->verifyPassword(null));

        $user->setPassword();
        $this->assertFalse($user->verifyPassword($password1));
        $this->assertFalse($user->verifyPassword(''));
        $this->assertFalse($user->verifyPassword(null));
        $user->setPassword(null);
        $this->assertFalse($user->verifyPassword($password1));
        $this->assertFalse($user->verifyPassword(''));
        $this->assertFalse($user->verifyPassword(null));
        $user->setPassword('');
        $this->assertFalse($user->verifyPassword($password1));
        $this->assertFalse($user->verifyPassword(''));
        $this->assertFalse($user->verifyPassword(null));
    }

    /**
     * Try setting the password of a non-existing user.
     *
     * @return null
     */
    public function testSetPasswordInvalidUser()
    {
        global $username1, $password1;
        
        $user = new RestAuthUser($this->conn, $username1);
        try {
            $user->setPassword($password1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
            $this->assertEquals(array(), RestAuthUser::getAll($this->conn));
        }
    }

    /**
     * Try setting a password that is too short.
     *
     * @return null
     */
    public function testSetTooShortPasswort()
    {
        global $username1, $password1;
        
        $user = RestAuthUser::create($this->conn, $username1, $password1);
        try {
            $user->setPassword("x");
            $this->fail();
        } catch (RestAuthPreconditionFailed $e) {
            $this->assertFalse($user->verifyPassword("x"));
            $this->assertTrue($user->verifyPassword($password1));
        }
    }

    /**
     * Try getting a non-existing user.
     *
     * @return null
     */
    public function testGetInvalidUser()
    {
        global $username1, $password1;

        try {
            RestAuthUser::get($this->conn, $username1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }

    /**
     * Try removing a user.
     *
     * @return null
     */
    public function testRemoveUser()
    {
        global $username1, $password1;
        $user = RestAuthUser::create($this->conn, $username1, $password1);
    
        $user->remove();
        $this->assertEquals(array(), RestAuthUser::getAll($this->conn));
        try {
            RestAuthUser::get($this->conn, $username1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }
    }

    /**
     * Try removing a non-existing user.
     *
     * @return null
     */
    public function testRemoveInvalidUser()
    {
        global $username1, $password1;
        
        $user = new RestAuthUser($this->conn, $username1);
        try {
            $user->remove();
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->getType());
        }

    }

    /**
     * Testing users for equality.
     *
     * @return null
     */
    public function testEqualUsers()
    {
        global $username1;
        $user1 = new RestAuthUser($this->conn, $username1);
        $user2 = new RestAuthUser($this->conn, $username1);
        
        $this->assertEquals(0, RestAuthUser::cmp($user1, $user2));
        $this->assertEquals(0, RestAuthUser::cmp($user2, $user1));
    }
    
    /**
     * Testing users for unequality.
     *
     * @return null
     */
    public function testUnequalUsers()
    {
        $user1 = new RestAuthUser($this->conn, 'abc');
        $user2 = new RestAuthUser($this->conn, 'xyz');
        $this->assertEquals(-1, RestAuthUser::cmp($user1, $user2));
        $this->assertEquals(1, RestAuthUser::cmp($user2, $user1));
    }
}
?>
