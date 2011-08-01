<?php

/**
 * This file contains code related to HTTP handling.
 * 
 * @package php-restauth
 */

abstract class ContentHandler
{
    abstract function unmarshal_str($obj);
    abstract function unmarshal_list($obj);
    abstract function unmarshal_dict($obj);
    abstract function get_mime_type();
}

class RestAuthJsonHandler extends ContentHandler
{
    public function unmarshal_str($obj)
    {
        $arr = json_decode($obj);
        return $arr[0];
    }
    
    public function unmarshal_list($obj)
    {
    }
    
    public function unmarshal_dict($obj)
    {
    }
    
    public function get_mime_type()
    {
        return 'application/json';
    }
}

/**
 * An instance of this class represents a connection to a RestAuth service. 
 *
 * An instance of this class needs to be passed to any constructor of a
 * {@link RestAuthResource} or the respective factory methods.
 * 
 * @package php-restauth
 */
class RestAuthConnection
{
    public static $connection;

    /**
     * A simple constructor.
     *
     * Note that instantiating an object of this class does not invoke any
     * network connection by itself. Due to the statelessnes nature of HTTP i.e.
     * an unavailable service will only trigger an error when actually doing a
     * request.
     *
     * @param string $host The hostname of the RestAuth service
     * @param string $user The username to use for authenticating with the
     *     RestAuth service.
     * @param string $password The password to use for authenticating with the
     *     RestAuth service.
     */
    public function __construct($host, $user, $password)
    {
        $this->host = rtrim($host, '/');
        $this->set_credentials($user, $password);
        $this->handler = new RestAuthJsonHandler();

        self::$connection = $this;
    }

    /**
     * Factory method to reuse existing connection objects. The parameters
     * of this method are not used at all if the connection is already defined,
     * otherwise they are passed unmodified to
     * {@link RestAuthConnection::__construct __construct}.
     */
    public static function get_connection($host='', $user='', $password='')
    {
        if (!isset(self::$connection)) {
            self::$connection = new RestAuthConnection($host, $user, $password);
        }
        return self::$connection;
    }

    /**
     * Set the authentication credentials used when accessing the RestAuth
     * service. This method is already invoked by the constructor, so you only
     * have to call it when they change for some reason.
     * 
     * @param string $user The username to use
     * @param string $password The password to use
     */
    public function set_credentials($user, $password)
    {
        $this->auth_header = base64_encode($user . ':' . $password);
    }

    /**
     * Send an HTTP request to the RestAuth service. 
     *
     * This method is called by the {@link RestAuthConnection::get() get}, 
     * {@link RestAuthConnection::post() post}, 
     * {@link RestAuthConnection::put() put} and
     * {@link RestAuthConnection::delete() delete} methods. 
     * This method takes care of service authentication, encryption
     * and sets the Accept headers. 
     *
     * @param HttpRequest $request The request to use.
     * @link http://www.php.net/manual/en/class.httprequest.php HttpRequest
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *     failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    suffers from an internal error.
     * @throws {@link RestAuthRuntimeException} When some HTTP related error
     *    occurs.
     */
    public function send($request)
    { 
        # add headers present with all methods:
        $request->addHeaders(
            array(
                'Accept'        => $this->handler->get_mime_type(),
                'Authorization' => 'Basic ' . $this->auth_header,
            )
        );

        try {
            $response = $request->send();
        } catch (HttpException $ex) {
            throw new RestAuthHttpException($ex);
        }
        $response_headers = $response->getHeaders();

        # handle error status codes
        switch ($response->getResponseCode()) {
            case 401:
                throw new RestAuthUnauthorized($response);
            
            case 406:
                throw new RestAuthNotAcceptable($response);
                
            case 500:
                throw new RestAuthInternalServerError($response);
        }

        return $response;
    }

    /**
     * Perform a GET request on the connection. This method takes care
     * of escaping parameters and assembling the correct URL. This
     * method internally calls the {@link RestAuthConnection::send() send}
     * function to perform service authentication.
     * 
     * @param string $url The URL to perform the GET request on. The URL must
     *     not include a query string.
     * @param array $params Optional query parameters for this request.
     * @param array $headers Additional headers to send with this request.
     *
     * @return HttpMessage The response to the request.
     * @link http://www.php.net/manual/en/class.httpmessage.php HttpMessage
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *     failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    suffers from an internal error.
     * @throws {@link RestAuthRuntimeException} When some HTTP related error
     *    occurs.
     */
    public function get($url, $params = array(), $headers = array())
    {
        $url = $this->host . $this->sanitize_url($url);
        $options = array('headers' => $headers);
        $request = new HttpRequest($url, HTTP_METH_GET, $options);
        $request->setQueryData($params);
        return $this->send($request);
    }

    /**
     * Perform a POST request on the connection. This method takes care
     * of escaping parameters and assembling the correct URL. This
     * method internally calls the {@link RestAuthConnection::send() send}
     * function to perform service authentication.
     * 
     * @param string $url The URL to perform the POST request on. The URL
     *    must not include a query string.
     * @param array $params Query parameters for this request.
     * @param array $headers Additional headers to send with this request.
     *
     * @return HttpMessage The response to the request.
     * @link http://www.php.net/manual/en/class.httpmessage.php HttpMessage
     *
     * @throws {@link RestAuthBadRequest} If the server was unable to parse
     *    the request body.
     * @throws {@link RestAuthUnauthorized} When service authentication
     *     failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthUnsupportedMediaType} The server does not
     *     support the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    suffers from an internal error.
     * @throws {@link RestAuthRuntimeException} When some HTTP related error
     *    occurs.
     */
    public function post($url, $params, $headers = array())
    {
        $headers['Content-Type'] = $this->handler->get_mime_type();

        $url = $this->host . $this->sanitize_url($url);
        $options = array('headers' => $headers);

        $request = new HttpRequest($url, HTTP_METH_POST, $options);
        $request->setRawPostData(json_encode($params, JSON_FORCE_OBJECT));

        $response = $this->send($request);

        switch ($response->getResponseCode()) {
            case 400:
                throw new RestAuthBadRequest($response);
            
            case 415:
                throw new RestAuthUnsupportedMediaType($response);
        }
        return $response;
    }

    /**
     * Perform a PUT request on the connection. This method takes care
     * of escaping parameters and assembling the correct URL. This
     * method internally calls the {@link RestAuthConnection::send() send}
     * function to perform service authentication.
     * 
     * @param string $url The URL to perform the PUTrequest on. The URL must
     *     not include a query string.
     * @param array $params Query parameters for this request.
     * @param array $headers Additional headers to send with this request.
     *
     * @return HttpMessage The response to the request.
     * @link http://www.php.net/manual/en/class.httpmessage.php HttpMessage
     *
     * @throws {@link RestAuthBadRequest} If the server was unable to parse
     *    the request body.
     * @throws {@link RestAuthUnauthorized} When service authentication
     *     failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthUnsupportedMediaType} The server does not
     *     support the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    suffers from an internal error.
     * @throws {@link RestAuthRuntimeException} When some HTTP related error
     *    occurs.
     */
    public function put($url, $params, $headers = array())
    {
        $headers['Content-Type'] = 'application/json';
        
        $url = $this->host . $this->sanitize_url($url);
        $options = array('headers' => $headers);

        $request = new HttpRequest($url, HTTP_METH_PUT, $options);
        $request->setPutData(json_encode($params, JSON_FORCE_OBJECT));
        $response = $this->send($request);

        switch ($response->getResponseCode()) {
            case 400:
                throw new RestAuthBadRequest($response);
                break;
            
            case 415:
                throw new RestAuthUnsupportedMediaType($response);
                break;
        }
        return $response;
    }

    /**
     * Perform a DELETE request on the connection. This method takes care
     * of escaping parameters and assembling the correct URL. This
     * method internally calls the {@link RestAuthConnection::send() send}
     * function to perform service authentication.
     * 
     * @param string $url The URL to perform the DELETE request on. The URL
     *     must not include a query string.
     * @param array $headers Additional headers to send with this request.
     *
     * @return HttpMessage The response to the request.
     * @link http://www.php.net/manual/en/class.httpmessage.php HttpMessage
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *     failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    suffers from an internal error.
     */
    public function delete($url, $headers = array())
    {
        $url = $this->host . $this->sanitize_url($url);
        $options = array('headers' => $headers);
        $request = new HttpRequest($url, HTTP_METH_DELETE, $options);
        return $this->send($request);
    }
    
    /**
     * Sanitize the path segment of an URL. Makes sure it ends with a slash,
     * contains no double slashes and performs character escaping.
     *
     * @param string $url The path segment of an URL. Please note that this
     *     should not contain the query part ("?...") or the domain.
     * @return string The sanitized path segmet of an URL
     * @todo rename to sanitize_path
     */
    public function sanitize_url($url)
    {
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        $parts = array();
        foreach(explode('/', $url) as $part) {
            $part = rawurlencode($part);
            $parts[] = $part;
        }
        $url = implode('/', $parts);

        return $url;
    }

}

/**
 * Superclass for {@link RestAuthUser} and {@link RestAuthGroup} objects.
 * Exists to wrap http requests with the prefix of the given resource.
 * 
 * @package php-restauth
 */
abstract class RestAuthResource
{
    /**
     * Perform a GET request on the connection that was passed via the
     * constructor.
     *
     * This method prefixes the URL parameter with the resources class prefix
     * ('/users/' or '/groups/') and passes all parameters (otherwise
     * unmodified) to {@link RestAuthConnection::get()}.
     *
     * @param string $url The URL to perform the GET request on. The URL must
     *     not include a query string.
     * @param array $params Optional query parameters for this request.
     * @param array $headers Additional headers to send with this request.
     * @param string $prefix Modify the prefix used for this request
     *
     * @return HttpMessage The response to the request.
     * @link http://www.php.net/manual/en/class.httpmessage.php HttpMessage
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *     failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    suffers from an internal error.
     * @throws {@link RestAuthRuntimeException} When some HTTP related error
     *    occurs.
     */
    protected function _get($url, $params = array(), $headers = array())
    {
        $url = static::prefix . $url;
        return $this->conn->get($url, $params, $headers);
    }

    /**
     * Perform a POST request on the connection that was passed via the
     * constructor.
     *
     * This method prefixes the URL parameter with the resources class prefix
     * ('/users/' or '/groups/') and passes all parameters (otherwise
     * unmodified) to {@link RestAuthConnection::post()}.
     *
     * @param string $url The URL to perform the POST request on. The URL must
     *     not include a query string.
     * @param array $params Optional query parameters for this request.
     * @param array $headers Additional headers to send with this request.
     * @param string $prefix Modify the prefix used for this request
     *
     * @return HttpMessage The response to the request.
     * @link http://www.php.net/manual/en/class.httpmessage.php HttpMessage
     *
     * @throws {@link RestAuthBadRequest} When the request body could not be
     *    parsed. This should only happen with POST or PUT requests.
     * @throws {@link RestAuthUnauthorized} When service authentication
     *     failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthUnsupportedMediaType} The server does not
     *     support the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    suffers from an internal error.
     * @throws {@link RestAuthRuntimeException} When some HTTP related error
     *    occurs.
     */
    protected function _post($url, $params = array(), $headers = array())
    {
        $url = static::prefix . $url;
        return $this->conn->post($url, $params, $headers);
    }

    /**
     * Perform a PUT request on the connection that was passed via the
     * constructor.
     *
     * This method prefixes the URL parameter with the resources class prefix
     * ('/users/' or '/groups/') and passes all parameters (otherwise
     * unmodified) to {@link RestAuthConnection::put()}.
     *
     * @param string $url The URL to perform the PUT request on. The URL must
     *     not include a query string.
     * @param array $params Optional query parameters for this request.
     * @param array $headers Additional headers to send with this request.
     * @param string $prefix Modify the prefix used for this request
     *
     * @return HttpMessage The response to the request.
     * @link http://www.php.net/manual/en/class.httpmessage.php HttpMessage
     *
     * @throws {@link RestAuthBadRequest} When the request body could not be
     *    parsed. This should only happen with POST or PUT requests.
     * @throws {@link RestAuthUnauthorized} When service authentication
     *     failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthUnsupportedMediaType} The server does not
     *     support the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    suffers from an internal error.
     * @throws {@link RestAuthRuntimeException} When some HTTP related error
     *    occurs.
     */
    protected function _put($url, $params = array(), $headers = array())
    {
        $url = static::prefix . $url;
        return $this->conn->put($url, $params, $headers);
    }

    /**
     * Perform a DELETE request on the connection that was passed via the
     * constructor.
     *
     * This method prefixes the URL parameter with the the resources class
     * prefix ('/users/' or '/groups/') and passes all parameters (otherwise
     * unmodified) to {@link RestAuthConnection::delete()}.
     *
     * @param string $url The URL to perform the DELETE request on. The URL 
     *    must not include a query string.
     * @param array $headers Additional headers to send with this request.
     * @param string $prefix Modify the prefix used for this request
     *
     * @return HttpMessage The response to the request.
     * @link http://www.php.net/manual/en/class.httpmessage.php HttpMessage
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *     failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    suffers from an internal error.
     * @throws {@link RestAuthRuntimeException} When some HTTP related error
     *    occurs.
     */
    protected function _delete($url, $headers = array())
    {
        return $this->conn->delete(static::prefix . $url, $headers);
    }
}
?>
