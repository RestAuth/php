<?php

require_once 'PHPUnit/Framework.php';
require_once 'RestAuth/restauth.php';

class FakeContentHandler extends ContentHandler
{
    public function unmarshalStr($obj)
    {
        return "wrong";
    }
    
    public function unmarshalList($obj)
    {
        return array("wrong");
    }
    
    public function unmarshalDict($obj)
    {
        return array("wrong key" => "wrong value");
    }
    
    public function marshalDict($obj) {
        return '';
    }

    public function getMimeType()
    {
        return 'something/wrong';
    }    
}

class ConnectionTest extends PHPUnit_Framework_TestCase
{
    public function testConnection()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection( $RestAuthHost, $RestAuthUser, $RestAuthPass );
        
        RestAuthUser::getAll( $conn );
    }
    
    public function testWrongUser()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection($RestAuthHost, "wrong user", $RestAuthPass);
        
        try {
            RestAuthUser::getAll( $conn );
            $this->fail();
        } catch (RestAuthUnauthorized $e) {
        }
    }
    
    public function testWrongPassword()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection($RestAuthHost, $RestAuthUser, "wrong password");
        
        try {
            RestAuthUser::getAll( $conn );
            $this->fail();
        } catch (RestAuthUnauthorized $e) {
        }
    }
    
    public function testWrongPort()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection('http://[::1]:8001', $RestAuthUser, $RestAuthPass);
        
        try {
            RestAuthUser::getAll( $conn );
            $this->fail();
        } catch (RestAuthHttpException $e) {
            $cause = $e->getCause();
            if ( ! is_a( $cause, 'HttpInvalidParamException') ) {
                $this->fail();
            }
        }
    }
    
    public function testWrongHost()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection('http://[::3]:8000', $RestAuthUser, $RestAuthPass);
        
        try {
            RestAuthUser::getAll( $conn );
            $this->fail();
        } catch (RestAuthHttpException $e) {
            $cause = $e->getCause();
            if ( ! is_a( $cause, 'HttpInvalidParamException') ) {
                $this->fail();
            }
        }
    }
    
    public function testNotAcceptable()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection( $RestAuthHost, $RestAuthUser, $RestAuthPass );
        $conn->handler = new FakeContentHandler();
        
        try {
            RestAuthUser::getAll( $conn );
            $this->fail();
        } catch (RestAuthNotAcceptable $e) {
        }
        
        try {
            RestAuthGroup::getAll( $conn );
            $this->fail();
        } catch (RestAuthNotAcceptable $e) {
        }
    }
    
    public function testBadRequestPost()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection( $RestAuthHost, $RestAuthUser, $RestAuthPass );
        
        $params = array('whatever' => "foobar");
        try {
            $resp = $conn->post('/users/', $params);
            $this->fail();
        } catch (RestAuthBadRequest $e) {
            $this->assertEquals(array(), RestAuthUser::getAll($conn));
        }
    }
    
    public function testUnsupportedMediaTypePost()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass, $username1;
        $conn = new RestAuthConnection( $RestAuthHost, $RestAuthUser, $RestAuthPass );
        
        $conn->handler = new FakeContentHandler();
        
        $params = array('whatever' => "foobar");
        try {
            $resp = $conn->post('/users/', $params);
            $this->fail();
        } catch (RestAuthUnsupportedMediaType $e) {
            $conn->handler = new RestAuthJsonHandler();
            $this->assertEquals(array(), RestAuthUser::getAll($conn));
        }
    }
    
    public function testBadRequestPut()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass, $username1, $password1;
        $conn = new RestAuthConnection( $RestAuthHost, $RestAuthUser, $RestAuthPass );
        $user = RestAuthUser::create( $conn, $username1, $password1 );
        
        $params = array( 'bad' => 'request' );
        try {
            // change password
            $resp = $conn->put('/users/'.$username1.'/', $params);
            $this->fail();
        } catch (RestAuthBadRequest $e) {
            $conn->handler = new RestAuthJsonHandler();
            
            $this->assertEquals(array($user), RestAuthUser::getAll($conn));
            $this->assertTrue($user->verifyPassword( $password1 ));
        }
    }
    
    public function testUnsupportedMediaTypePut()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass, $username1, $password1, $password2;
        $conn = new RestAuthConnection( $RestAuthHost, $RestAuthUser, $RestAuthPass );
        $user = RestAuthUser::create( $conn, $username1, $password1 );
        
        $conn->handler = new FakeContentHandler();
        
        $params = array( 'password' => $password2 );
        try {
            // change password
            $resp = $conn->put('/users/'.$username1.'/', $params);
            $this->fail();
        } catch (RestAuthUnsupportedMediaType $e) {
            $conn->handler = new RestAuthJsonHandler();
            
            $this->assertEquals(array($user), RestAuthUser::getAll($conn));
            $this->assertTrue($user->verifyPassword( $password1 ));
            $this->assertFalse($user->verifyPassword( $password2 ));
        }
        
    }
    
    public function setUp() {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = RestAuthConnection::getConnection(
            $RestAuthHost, $RestAuthUser, $RestAuthPass
        );

        $users = RestAuthUser::getAll($conn);
        if (count($users)) {
            throw new Exception("Found " . count($users) . " left over users.");
        }
    }
    
    public function tearDown()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection( $RestAuthHost, $RestAuthUser, $RestAuthPass );
        
        $users = RestAuthUser::getAll($conn);
        foreach ($users as $user) {
            $user->remove();
        }
    }
    
    public static function tearDownAfterClass() {
        RestAuthConnection::$connection = null;
    }
}
?>