<?php

require_once('PHPUnit/Framework.php');
require_once('RestAuth/restauth.php');

// setup the connection:
$RestAuthHost = 'http://[::1]:8000';
$RestAuthUser = 'vowi';
$RestAuthPass = 'vowi';
$conn = new RestAuthConnection($RestAuthHost, $RestAuthUser, $RestAuthPass);

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
        global $conn;

        $users = RestAuthUser::get_all($conn);
        if (count($users)) {
            throw new Exception("Found " . count($users) . " left over users.");
        }
    }
    public function tearDown()
    {
        global $conn;

        $users = RestAuthUser::get_all($conn);
        foreach ($users as $user) {
            $user->remove();
        }
    }

    public function testCreateUser()
    {
        global $conn, $username1, $password1;

        $user = RestAuthUser::create($conn, $username1, $password1);

        $this->assertEquals(array($user), RestAuthUser::get_all($conn));
        $this->assertEquals($user, RestAuthUser::get($conn, $username1));
    }

    public function testCreateUserNoPassword()
    {
        global $conn, $username1, $username2, $username3, $password1;

        $user = RestAuthUser::create($conn, $username1);
        $this->assertEquals(array($user), RestAuthUser::get_all($conn));
        $this->assertEquals($user, RestAuthUser::get($conn, $username1));

        $this->assertFalse($user->verify_password($password1));
        $this->assertFalse($user->verify_password(''));
        $this->assertFalse($user->verify_password(NULL));

        $user = RestAuthUser::create($conn, $username2, '');
        $this->assertEquals($user, RestAuthUser::get($conn, $username2));

        $this->assertFalse($user->verify_password($password1));
        $this->assertFalse($user->verify_password(''));
        $this->assertFalse($user->verify_password(NULL));

        $user = RestAuthUser::create($conn, $username3, NULL);
        $this->assertEquals($user, RestAuthUser::get($conn, $username3));

        $this->assertFalse($user->verify_password($password1));
        $this->assertFalse($user->verify_password(''));
        $this->assertFalse($user->verify_password(NULL));
    }

    public function testCreateInvalidUser()
    {
        global $conn, $username1, $password1;

        try {
            RestAuthUser::create($conn, "foo/bar", "don't care");
            $this->fail();
        } catch (RestAuthPreconditionFailed $e) {
            $this->assertEquals(array(), RestAuthUser::get_all($conn));
        }
    }
    public function testCreateUserTwice()
    {
        global $conn, $username1, $password1;
        $new_pass = "new " . $password1;

        $user = RestAuthUser::create($conn, $username1, $password1);
        $this->assertEquals($user, RestAuthUser::get($conn, $username1));

        try {
            RestAuthUser::create($conn, $username1, $new_pass);
            $this->fail();
        } catch (RestAuthUserExists $e) {
            $this->assertTrue($user->verify_password($password1));
            $this->assertFalse($user->verify_password($new_pass));
        }
    }
    public function testVerifyPassword()
    {
        global $conn, $username1, $password1;
        $user = RestAuthUser::create($conn, $username1, $password1);

        $this->assertTrue($user->verify_password($password1));
        $this->assertFalse($user->verify_password("something else"));
    }

    public function testVerifyPasswordInvalidUser()
    {
        global $conn, $username1, $password1;
        
        $user = new RestAuthUser($conn, $username1);

        $this->assertFalse($user->verify_password("foobar"));
    }

    public function testSetPassword()
    {
        global $conn, $username1, $password1;
        $new_pass = "something else";


        $user = RestAuthUser::create($conn, $username1, $password1);
        $this->assertTrue($user->verify_password($password1));
        $this->assertFalse($user->verify_password($new_pass));

        $user->set_password($new_pass);

        $this->assertFalse($user->verify_password($password1));
        $this->assertTrue($user->verify_password($new_pass));
    }

    public function testDisablePassword()
    {
        global $conn, $username1, $password1;
        $user = RestAuthUser::create($conn, $username1, $password1);
        $this->assertTrue($user->verify_password($password1));
        $this->assertFalse($user->verify_password(''));
        $this->assertFalse($user->verify_password(NULL));

        $user->set_password();
        $this->assertFalse($user->verify_password($password1));
        $this->assertFalse($user->verify_password(''));
        $this->assertFalse($user->verify_password(NULL));
        $user->set_password(NULL);
        $this->assertFalse($user->verify_password($password1));
        $this->assertFalse($user->verify_password(''));
        $this->assertFalse($user->verify_password(NULL));
        $user->set_password('');
        $this->assertFalse($user->verify_password($password1));
        $this->assertFalse($user->verify_password(''));
        $this->assertFalse($user->verify_password(NULL));
    }

    public function testSetPasswordInvalidUser()
    {
        global $conn, $username1, $password1;
        
        $user = new RestAuthUser($conn, $username1);
        try {
            $user->set_password($password1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->get_type());
            $this->assertEquals(array(), RestAuthUser::get_all($conn));
        }
    }

    public function testSetTooShortPasswort()
    {
        global $conn, $username1, $password1;
        
        $user = RestAuthUser::create($conn, $username1, $password1);
        try {
            $user->set_password("x");
            $this->fail();
        } catch (RestAuthPreconditionFailed $e) {
            $this->assertFalse($user->verify_password("x"));
            $this->assertTrue($user->verify_password($password1));
        }
    }

    public function testGetInvalidUser()
    {
        global $conn, $username1, $password1;

        try {
            RestAuthUser::get($conn, $username1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->get_type());
        }
    }

    public function testRemoveUser()
    {
        global $conn, $username1, $password1;
        $user = RestAuthUser::create($conn, $username1, $password1);
    
        $user->remove();
        $this->assertEquals(array(), RestAuthUser::get_all($conn));
        try {
            RestAuthUser::get($conn, $username1);
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->get_type());
        }
    }

    public function testRemoveInvalidUser()
    {
        global $conn, $username1, $password1;
        
        $user = new RestAuthUser($conn, $username1);
        try {
            $user->remove();
            $this->fail();
        } catch (RestAuthResourceNotFound $e) {
            $this->assertEquals("user", $e->get_type());
        }

    }
}

?>
