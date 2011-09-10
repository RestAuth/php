<?php
/**
 * This file does some basic connection tests.
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

/**
 * This class is used to marshal wrong objects and such to trigger certain
 * errror conditions that otherwise only occur with faulty implementations.
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
class FakeContentHandler extends ContentHandler
{
    /**
     * Not really unmarshal a string.
     *
     * @param object $obj not used.
     * 
     * @return str always 'wrong'.
     */
    public function unmarshalStr($obj)
    {
        return "wrong";
    }
    
    /**
     * Not really unmarshal a list.
     *
     * @param object $obj not used.
     * 
     * @return array always array('wrong').
     */
    public function unmarshalList($obj)
    {
        return array("wrong");
    }
    
    /**
     * Not really unmarshal a dictionary.
     *
     * @param object $obj not used.
     * 
     * @return str always array("wrong key" => "wrong value").
     */
    public function unmarshalDict($obj)
    {
        return array("wrong key" => "wrong value");
    }
    
    /**
     * Not really marshal a dictionary.
     *
     * @param object $obj not used.
     * 
     * @return str always ''.
     */
    public function marshalDict($obj)
    {
        return '';
    }
    
    /**
     * Get a bogus mime type.
     *
     * @return str always 'something/wrong'.
     */
    public function getMimeType()
    {
        return 'something/wrong';
    }    
}

/**
 * Do various connection related tests.
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
class ConnectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * Basic test that should work.
     *
     * @return null
     */
    public function testConnection()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection($RestAuthHost, $RestAuthUser, $RestAuthPass);
        
        RestAuthUser::getAll($conn);
    }
    
    /**
     * Try to authenticate with the wrong username.
     *
     * @return null
     */
    public function testWrongUser()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection($RestAuthHost, "wrong user", $RestAuthPass);
        
        try {
            RestAuthUser::getAll($conn);
            $this->fail();
        } catch (RestAuthUnauthorized $e) {
        }
    }
    
    /**
     * Try to authenticate with the wrong password.
     *
     * @return null
     */
    public function testWrongPassword()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection($RestAuthHost, $RestAuthUser, "wrong");
        
        try {
            RestAuthUser::getAll($conn);
            $this->fail();
        } catch (RestAuthUnauthorized $e) {
        }
    }
    
    /**
     * Try to connect to the wrong port.
     *
     * @return null
     */
    public function testWrongPort()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $host = 'http://[::1]:8001';
        $conn = new RestAuthConnection($host, $RestAuthUser, $RestAuthPass);
        
        try {
            RestAuthUser::getAll($conn);
            $this->fail();
        } catch (RestAuthHttpException $e) {
            $cause = $e->getCause();
            if (! is_a($cause, 'HttpInvalidParamException')) {
                $this->fail();
            }
        }
    }
    
    /**
     * Try to connect to the wrong host.
     *
     * @return null
     */
    public function testWrongHost()
    {
        global $RestAuthUser, $RestAuthPass;
        $host = 'http://[::3]:8000';
        $conn = new RestAuthConnection($host, $RestAuthUser, $RestAuthPass);
        
        try {
            RestAuthUser::getAll($conn);
            $this->fail();
        } catch (RestAuthHttpException $e) {
            $cause = $e->getCause();
            if (! is_a($cause, 'HttpInvalidParamException')) {
                $this->fail();
            }
        }
    }
    
    /**
     * Try to accept content in a format that can not be generated by the
     * server.
     *
     * @return null
     */
    public function testNotAcceptable()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection($RestAuthHost, $RestAuthUser, $RestAuthPass);
        $conn->handler = new FakeContentHandler();
        
        try {
            RestAuthUser::getAll($conn);
            $this->fail();
        } catch (RestAuthNotAcceptable $e) {
        }
        
        try {
            RestAuthGroup::getAll($conn);
            $this->fail();
        } catch (RestAuthNotAcceptable $e) {
        }
    }
    
    /**
     * Try making a bad POST request.
     *
     * @return null
     */
    public function testBadRequestPost()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection($RestAuthHost, $RestAuthUser, $RestAuthPass);
        
        $params = array('whatever' => "foobar");
        try {
            $resp = $conn->post('/users/', $params);
            $this->fail();
        } catch (RestAuthBadRequest $e) {
            $this->assertEquals(array(), RestAuthUser::getAll($conn));
        }
    }
    
    /**
     * Try a POST request with serialized data in an unsupported media format. 
     *
     * @return null
     */
    public function testUnsupportedMediaTypePost()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass, $username1;
        $conn = new RestAuthConnection($RestAuthHost, $RestAuthUser, $RestAuthPass);
        
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
    
    /**
     * Try making a bad PUT request.
     *
     * @return null
     */
    public function testBadRequestPut()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass, $username1, $password1;
        $conn = new RestAuthConnection($RestAuthHost, $RestAuthUser, $RestAuthPass);
        $user = RestAuthUser::create($conn, $username1, $password1);
        
        $params = array('bad' => 'request');
        try {
            // change password
            $resp = $conn->put('/users/'.$username1.'/', $params);
            $this->fail();
        } catch (RestAuthBadRequest $e) {
            $conn->handler = new RestAuthJsonHandler();
            
            $this->assertEquals(array($user), RestAuthUser::getAll($conn));
            $this->assertTrue($user->verifyPassword($password1));
        }
    }
    
    /**
     * Try a PUT request with serialized data in an unsupported media format. 
     *
     * @return null
     */
    public function testUnsupportedMediaTypePut()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass, $username1,
            $password1, $password2;
        $conn = new RestAuthConnection(
            $RestAuthHost, $RestAuthUser, $RestAuthPass
        );
        $user = RestAuthUser::create($conn, $username1, $password1);
        
        $conn->handler = new FakeContentHandler();
        
        $params = array('password' => $password2);
        try {
            // change password
            $resp = $conn->put('/users/'.$username1.'/', $params);
            $this->fail();
        } catch (RestAuthUnsupportedMediaType $e) {
            $conn->handler = new RestAuthJsonHandler();
            
            $this->assertEquals(array($user), RestAuthUser::getAll($conn));
            $this->assertTrue($user->verifyPassword($password1));
            $this->assertFalse($user->verifyPassword($password2));
        }
        
    }
    
    /**
     * Set up everything for the test.
     *
     * @return null
     */
    public function setUp()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = RestAuthConnection::getConnection(
            $RestAuthHost, $RestAuthUser, $RestAuthPass
        );

        $users = RestAuthUser::getAll($conn);
        if (count($users)) {
            throw new Exception("Found " . count($users) . " left over users.");
        }
    }
    
    /**
     * Remove all data created by any test, etc.
     *
     * @return null
     */
    public function tearDown()
    {
        global $RestAuthHost, $RestAuthUser, $RestAuthPass;
        $conn = new RestAuthConnection($RestAuthHost, $RestAuthUser, $RestAuthPass);
        
        $users = RestAuthUser::getAll($conn);
        foreach ($users as $user) {
            $user->remove();
        }
    }
    
    /**
     * Total cleanup after this test suite.
     *
     * @return null
     */
    public static function tearDownAfterClass()
    {
        RestAuthConnection::$connection = null;
    }
}
?>