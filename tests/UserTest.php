<?php

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

class UserTest extends PHPUnit_Framework_TestCase
{
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
    public function tearDown()
    {
        $users = RestAuthUser::getAll($this->conn);
        foreach ($users as $user) {
            $user->remove();
        }
    }

    public function testCreateUser()
    {
        global $username1, $password1;

        $user = RestAuthUser::create($this->conn, $username1, $password1);

        $this->assertEquals(array($user), RestAuthUser::getAll($this->conn));
        $this->assertEquals($user, RestAuthUser::get($this->conn, $username1));
    }

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
    public function testVerifyPassword()
    {
        global $username1, $password1;
        $user = RestAuthUser::create($this->conn, $username1, $password1);

        $this->assertTrue($user->verifyPassword($password1));
        $this->assertFalse($user->verifyPassword("something else"));
    }

    public function testVerifyPasswordInvalidUser()
    {
        global $username1, $password1;
        
        $user = new RestAuthUser($this->conn, $username1);

        $this->assertFalse($user->verifyPassword("foobar"));
    }

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

    public function testEqualUsers()
    {
        global $username1;
        $user1 = new RestAuthUser($this->conn, $username1);
        $user2 = new RestAuthUser($this->conn, $username1);
        
        $this->assertEquals(0, RestAuthUser::cmp($user1, $user2));
        $this->assertEquals(0, RestAuthUser::cmp($user2, $user1));
    }
    
    public function testUnequalUsers()
    {
        $user1 = new RestAuthUser($this->conn, 'abc');
        $user2 = new RestAuthUser($this->conn, 'xyz');
        $this->assertEquals(-1, RestAuthUser::cmp($user1, $user2));
        $this->assertEquals(1, RestAuthUser::cmp($user2, $user1));
    }
}
?>
