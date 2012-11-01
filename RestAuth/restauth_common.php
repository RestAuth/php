<?php

/**
 * This file contains code related to HTTP handling.
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
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @link      https://php.restauth.net
 */

/**
 * Abstract content handler.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
abstract class ContentHandler
{
    /**
     * Unmarshal a string.
     *
     * @param str $obj The serialized data to unmarshal
     *
     * @return str The unmarshalled string
     */
    abstract function unmarshalStr($obj);

    /**
     * Unmarshal a list.
     *
     * @param str $obj The serialized data to unmarshal
     *
     * @return str The unmarshalled list.
     */
    abstract function unmarshalList($obj);

    /**
     * Unmarshal a dictionary.
     *
     * @param str $obj The serialized data to unmarshal
     *
     * @return str The unmarshalled dictionary.
     */
    abstract function unmarshalDict($obj);

    /**
     * Marshal an array into a dictionary.
     *
     * @param array $arr The array to serialize.
     *
     * @return str The serialized array.
     */
    abstract function marshalDict($arr);

    /**
     * Get the mimetype that this class handles.
     *
     * @return str The MIME type handled by this class.
     */
    abstract function getMimeType();
}

/**
 * Handle JSON content.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthJsonHandler extends ContentHandler
{
    /**
     * Unmarshal a string.
     *
     * @param str $obj The serialized data to unmarshal.
     *
     * @return str The unmarshalled string.
     */
    public function unmarshalStr($obj)
    {
        $arr = json_decode($obj);
        return $arr[0];
    }

    /**
     * Unmarshal a list.
     *
     * @param str $obj The serialized data to unmarshal.
     *
     * @return str The unmarshalled list.
     */
    public function unmarshalList($obj)
    {
        return json_decode($obj);
    }

    /**
     * Unmarshal a dictionary.
     *
     * @param str $obj The serialized data to unmarshal.
     *
     * @return str The unmarshalled dictionary.
     */
    public function unmarshalDict($obj)
    {
        return (array) json_decode($obj);
    }

    /**
     * Marshal an array into a dictionary.
     *
     * @param array $arr The array to serialize.
     *
     * @return str The serialized array.
     */
    public function marshalDict($arr)
    {
        return json_encode($arr, JSON_FORCE_OBJECT);
    }

    /**
     * Get the content type that this class handles.
     *
     * @return str Always returns 'application/json'.
     */
    public function getMimeType()
    {
        return 'application/json';
    }
}

/**
 * A HTTP response. Since php5 provides no easy handling for this, we have to
 * implement this ourselves :-(
 */
class RestAuthHttpResponse
{
    private $_status;
    private $_response; // raw response as returned by curl_exec
    private $_raw_headers;
    private $_headers;
    private $_body;

    public function __construct($status, $response, $header_size)
    {
        $this->_status = $status;
        $this->_response = $response;
        $this->_header_size = $header_size;
    }

    public function getResponseCode()
    {
        return $this->_status;
    }

    public function getHeaders()
    {
        if (is_null($this->_headers)) {
            $this->parseHeaders();
        }

        return $this->_headers;
    }

    public function getHeader($field)
    {
        if (is_null($this->_headers)) {
            $this->parseHeaders();
        }

        return $this->_headers[$field];
    }

    public function getBody()
    {
        $this->parseBody();
        return utf8_encode($this->_body);
    }

    private function parseBody() {
        if (is_null($this->_body)) {
            $this->_raw_headers = substr($this->_response, 0, $this->_header_size);
            $this->_body = substr($this->_response, $this->_header_size);
        }
    }

    private function parseHeaders()
    {
        $this->parseBody();

        $headers = str_replace("\r", "", $this->_raw_headers);
        $headers = explode("\n", $headers);
        foreach ($headers as $value) {
            if (strpos($value, ':') === false) {
                continue;
            }

            $header = explode(": ", $value);
            $headerdata[$header[0]] = $header[1];
        }
        $this->_headers = $headerdata;
    }
}

/**
 * An instance of this class represents a connection to a RestAuth service.
 *
 * An instance of this class needs to be passed to any constructor of a
 * {@link RestAuthResource} or the respective factory methods.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
 */
class RestAuthConnection
{
    public static $connection;

    private $_handler;
    private $_headers = array();
    private $_curlOptions;
    private $_contenttype;

    /**
     * A simple constructor.
     *
     * Note that instantiating an object of this class does not invoke any
     * network connection by itself. Due to the statelessnes nature of HTTP i.e.
     * an unavailable service will only trigger an error when actually doing a
     * request.
     *
     * @param string         $url            The base URL of the RestAuth
     *     service
     * @param string         $user           The username to use for
     *     authenticating with the RestAuth service.
     * @param string         $password       The password to use for
     *     authenticating with the RestAuth service.
     * @param ContentHandler $contentHandler The content handler used.
     *     If null, a {@link RestAuthJsonContentHandler} will be used.
     * @param array          $curlOptions    Any additional curl options
     *     to pass to this connection. See the documentation of {@link
     *     http://www.php.net/manual/en/function.curl-setopt.php curl_setopt}
     *     for a list of possible values.
     * @param array          $headers        Any additional headers to pass with
     *      this request.
     */
    public function __construct($url, $user, $password, $contentHandler=null,
        $curlOptions=null, $headers=null
    ) {
        $this->url = rtrim($url, '/');
        $this->setCredentials($user, $password);
        $this->setContentHandler($contentHandler);

        $this->_curlOptions = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
        );

        // update any user-provided headers or options:
        if (!is_null($headers)) {
            $this->_headers = array_merge($this->_headers, $headers);
        }
        if (!is_null($curlOptions)) {
            $this->_curlOptions = array_merge(
                $this->_curlOptions, $curlOptions
            );
        }

        // set SSL options:
        //TODO: Document how to set this using the curlopts parameter
        //if ($this->ssl) {
        //    curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, $this->verifyhost);
        //    curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, $this->verifypeer);
        //}

        self::$connection = $this;
    }

    /**
     * Factory method to reuse existing connection objects. The parameters
     * of this method are not used at all if the connection is already defined,
     * otherwise they are passed unmodified to
     * {@link RestAuthConnection::__construct __construct}.
     *
     * @param string $host     The hostname of the RestAuth service
     * @param string $user     The username to use for authenticating with the
     *     RestAuth service.
     * @param string $password The password to use for authenticating with the
     *     RestAuth service.
     *
     * @return RestAuthConnection An instance of a RestAuthConnection.
     */
    public static function getConnection($host='', $user='', $password='')
    {
        if (!isset(self::$connection)) {
            return new RestAuthConnection($host, $user, $password);
        }
        return self::$connection;
    }

    /**
     * Set the authentication credentials used when accessing the RestAuth
     * service. This method is already invoked by the constructor, so you only
     * have to call it when they change for some reason.
     *
     * @param string $user     The username to use
     * @param string $password The password to use
     *
     * @return null
     */
    public function setCredentials($user, $password)
    {
        $value = 'Basic ' . base64_encode($user . ':' . $password);
        $this->_headers['auth'] = 'Authorization: ' . $value;
    }

    /**
     * Set the content handler used in this connection.
     *
     * @param object $handler The handler to use. If null, a
     *     {@link RestAuthJsonHandler} is used.
     *
     * @return null
     */
    public function setContentHandler($handler=null)
    {
        if (is_null($handler)) {
            $this->_handler = new RestAuthJsonHandler();
        } else {
            $this->_handler = $handler;
        }

        $mimetype = $this->_handler->getMimeType();
        $this->_contenttype = 'Content-Type: ' . $mimetype;
        $this->_headers['accept'] = 'Accept: ' . $mimetype;
    }

    /**
     * Unmarshal a string using the content type of this connection.
     *
     * @param string $str The string to unmarshal.
     *
     * @return string the unmarshalled string
     */
    public function unmarshalStr($str)
    {
        return $this->_handler->unmarshalStr($str);
    }

    /**
     * Unmarshal a dictionary using the content type of this connection.
     *
     * @param string $dict The dictionary to unmarshal.
     *
     * @return string the unmarshalled dictionary
     */
    public function unmarshalDict($dict)
    {
        return $this->_handler->unmarshalDict($dict);
    }

    /**
     * Unmarshal a list using the content type of this connection.
     *
     * @param string $list The list to unmarshal.
     *
     * @return array the unmarshalled list
     */
    public function unmarshalList($list)
    {
        return $this->_handler->unmarshalList($list);
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
     * @param string $method The HTTP method to call ('GET', 'POST', ...)
     * @param string $path   The path to call. The URL from the constructor is
     *     automatically prepended.
     * @param string $body   The HTTP body, if any.
     *
     * @return RestAuthHttpResponse The response from the RestAuth server.
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
    public function send($method, $path, $body=null)
    {
        // initialize curl handle:
        $curlHandle = curl_init($this->url . $path);
        $headers = $this->_headers;
        $curlOptions = $this->_curlOptions;

        $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;

        // set body if we POST/PUT:
        if (!is_null($body)) {
            $curlOptions[CURLOPT_POSTFIELDS] = $body;
            $headers[] = $this->_contenttype;;
        }

        // finally set all options at once:
        $curlOptions[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($curlHandle, $curlOptions);

        $result = curl_exec($curlHandle);
        if ($result === false) {
            throw new RestAuthHttpException();
        } else {
            $status = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);

            $response = new RestAuthHttpResponse($status, $result, $header_size);
        }

        // handle error status codes
        switch ($response->getResponseCode()) {
        case 401:
            throw new RestAuthUnauthorized($response);

        case 406:
            throw new RestAuthNotAcceptable($response);

            // @codeCoverageIgnoreStart
        case 500:
            throw new RestAuthInternalServerError($response);
        }
        // @codeCoverageIgnoreEnd

        return $response;
    }

    /**
     * Perform a GET request on the connection. This method takes care
     * of escaping parameters and assembling the correct URL. This
     * method internally calls the {@link RestAuthConnection::send() send}
     * function to perform service authentication.
     *
     * @param string $url    The URL to perform the GET request on. The URL must
     *     not include a query string.
     * @param array  $params Optional query parameters for this request.
     *
     * @return HttpMessage The response to the request.
     *
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
    public function get($url, $params = array())
    {
        $url = $this->sanitizePath($url);
        if (!($params == false)) {
            $url .= '?' . http_build_query($params);
        }
        return $this->send('GET', $url);
    }

    /**
     * Perform a POST request on the connection. This method takes care
     * of escaping parameters and assembling the correct URL. This
     * method internally calls the {@link RestAuthConnection::send() send}
     * function to perform service authentication.
     *
     * @param string $url    The URL to perform the POST request on. The URL
     *    must not include a query string.
     * @param array  $params Query parameters for this request.
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
    public function post($url, $params)
    {
        $url = $this->sanitizePath($url);
        $body = $this->_handler->marshalDict($params);

        $response = $this->send('POST', $url, $body);

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
     * @param string $url    The URL to perform the PUTrequest on. The URL must
     *     not include a query string.
     * @param array  $params Query parameters for this request.
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
    public function put($url, $params)
    {
        $url = $this->sanitizePath($url);
        $body = $this->_handler->marshalDict($params);

        $response = $this->send('PUT', $url, $body);

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
     * @param string $url The URL to perform the DELETE request on. The URL must
     *     not include a query string.
     *
     * @return HttpMessage The response to the request.
     * @link http://www.php.net/manual/en/class.httpmessage.php HttpMessage
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *     failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *     a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *     suffers from an internal error.
     */
    public function delete($url)
    {
        $url = $this->sanitizePath($url);
        return $this->send('DELETE', $url);
    }

    /**
     * Sanitize the path segment of an URL. Makes sure it ends with a slash,
     * contains no double slashes and performs character escaping.
     *
     * @param string $path The path segment of an URL. Please note that this
     *     should not contain the query part ("?...") or the domain.
     *
     * @return string The sanitized path segmet of an URL
     */
    protected function sanitizePath($path)
    {
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }

        $parts = array();
        foreach (explode('/', $path) as $part) {
            $part = rawurlencode($part);
            $parts[] = $part;
        }
        $path = implode('/', $parts);

        return $path;
    }

}

/**
 * Superclass for {@link RestAuthUser} and {@link RestAuthGroup} objects.
 * Exists to wrap http requests with the prefix of the given resource.
 *
 * @category  Authentication
 * @package   RestAuth
 * @author    Mathias Ertl <mati@restauth.net>
 * @copyright 2010-2011 Mathias Ertl
 * @license   http://www.gnu.org/licenses/gpl.html  GNU General Public Licence, version 3
 * @version   Release: @package_version@
 * @link      https://php.restauth.net
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
     * @param string $url    The URL to perform the GET request on. The URL must
     *     not include a query string.
     * @param array  $params Optional query parameters for this request.
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
    protected function getRequest($url, $params = array())
    {
        return $this->conn->get(static::PREFIX . $url, $params);
    }

    /**
     * Perform a POST request on the connection that was passed via the
     * constructor.
     *
     * This method prefixes the URL parameter with the resources class prefix
     * ('/users/' or '/groups/') and passes all parameters (otherwise
     * unmodified) to {@link RestAuthConnection::post()}.
     *
     * @param string $url    The URL to perform the POST request on. The URL must
     *     not include a query string.
     * @param array  $params Optional query parameters for this request.
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
    protected function postRequest($url, $params = array())
    {
        return $this->conn->post(static::PREFIX . $url, $params);
    }

    /**
     * Perform a PUT request on the connection that was passed via the
     * constructor.
     *
     * This method prefixes the URL parameter with the resources class prefix
     * ('/users/' or '/groups/') and passes all parameters (otherwise
     * unmodified) to {@link RestAuthConnection::put()}.
     *
     * @param string $url    The URL to perform the PUT request on. The URL must
     *     not include a query string.
     * @param array  $params Optional query parameters for this request.
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
    protected function putRequest($url, $params = array())
    {
        return $this->conn->put(static::PREFIX . $url, $params);
    }

    /**
     * Perform a DELETE request on the connection that was passed via the
     * constructor.
     *
     * This method prefixes the URL parameter with the the resources class
     * prefix ('/users/' or '/groups/') and passes all parameters (otherwise
     * unmodified) to {@link RestAuthConnection::delete()}.
     *
     * @param string $url The URL to perform the DELETE request on. The URL must
     *    not include a query string.
     *
     * @return HttpMessage The response to the request.
     * @link http://www.php.net/manual/en/class.httpmessage.php HttpMessage
     *
     * @throws {@link RestAuthUnauthorized} When service authentication
     *    failed.
     * @throws {@link RestAuthNotAcceptable} When the server cannot generate
     *    a response in the content type used by this connection.
     * @throws {@link RestAuthInternalServerError} When the RestAuth service
     *    suffers from an internal error.
     * @throws {@link RestAuthRuntimeException} When some HTTP related error
     *    occurs.
     */
    protected function deleteRequest($url)
    {
        return $this->conn->delete(static::PREFIX . $url);
    }
}
?>
