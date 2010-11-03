<?php

/**
 * This file contains code related to HTTP handling.
 * 
 * @package php-restauth
 */

/**
 * A standard HTTP response that also parses headers and makes them conveniently
 * available as an array.
 * 
 * @package php-restauth
 */
class HttpResponse {
	/**
	 * Constructor. 
	 *
	 * @param int $code The HTTP response code.
	 * @param string $body The body of the HTTP response.
	 * @param string $headers The headers of the HTTP response.
	 */
	public function __construct( $code, $body, $headers ) {
		$this->code = $code;
		$this->body = $body;
		$this->headers = array();

		foreach( explode( "\n", $headers ) as $header ) {
			$header = trim( $header );
			$pos = strpos( $header, ':' );
			if ( ! $pos ) {
				continue;
			}
			$key = substr( $header, 0, $pos );
			$val = substr( $header, $pos + 2);
			$this->headers[$key] = $val;
		}
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
class RestAuthConnection {
	/**
	 * A simple constructor.
	 *
	 * Note that instantiating an object of this class does not invoke any network
	 * connection by itself. Due to the statelessnes nature of HTTP i.e. an
	 * unavailable service will only trigger an error when actually doing a request.
	 *
	 * @param string $host The hostname of the RestAuth service
	 * @param int $port The port the RestAuth service listens on
	 * @param string $user The service name to use for authenticating with RestAuth
	 * @param string $password The password to use for authenticating with RestAuth.
	 * @param boolean $use_ssl Wether or not to use SSL
	 * @param string $cert The certificate to use when using SSL.
	 * @todo SSL is not handled at all so far, the parameters for it are not used at all.
	 */
	public function __construct( $host, $port, $user, $password, $use_ssl = true, $cert = '' ) {
		$this->host = rtrim( $host, '/' );
		$this->port = $port;
		$this->use_ssl = $use_ssl;
		$this->cert = $cert;

		if ( $use_ssl ) {
			$this->base_url = 'https://' . $this->host;
		} else {
			$this->base_url = 'http://' . $this->host;
		}


		$this->set_credentials( $user, $password );
	}

	/**
	 * Set the authentication credentials used when accessing the RestAuth
	 * service. This method is already invoked by the constructor, so you
	 * only have to call it when they change for some reason.
	 * 
	 * @param string $user The username to use
	 * @param string $password The password to use
	 */
	public function set_credentials( $user, $password ) {
		$this->user = $user;
		$this->password = $password;
		$this->auth_field = $user . ':' . $password;
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
	 * @param string $method The HTTP request method. Either "GET", "POST",
	 *	"PUT" or "DELETE".
	 * @param string $url The URL to perform the request on. 
	 * @param array $body The body for POST or PUT requests. This is assumed
	 *	to be valid JSON.
	 * @param array $headers Additional headers to send with this request.
	 *
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 *	parsed. This should only happen with POST or PUT requests.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 * 	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	suffers from an internal error.
	 */
	public function send( $method, $url, $body = '', $headers = array() ) {
		# build final url
		$url = $this->base_url . $url;

		# prepare headers:
		$headers[] = 'Accept: application/json';
#		print( $method . ' ' . $url . "\n" );
#		if( $body ) print( "Body: $body\n" );
		
		# initialize curl session
		$curl_session = curl_init();
		curl_setopt( $curl_session, CURLOPT_URL, $url );
		curl_setopt( $curl_session, CURLOPT_PORT, $this->port );
		curl_setopt( $curl_session, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt( $curl_session, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl_session, CURLOPT_HEADER, 1 );

		# set authorization
		curl_setopt( $curl_session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt( $curl_session, CURLOPT_USERPWD, $this->auth_field );

		# set body
		if ( $body ) {
			curl_setopt( $curl_session, CURLOPT_POSTFIELDS, $body );
			$headers[] = 'Content-Length: ' . strlen($body);
		}

		# finally set http headers:
		curl_setopt( $curl_session, CURLOPT_HTTPHEADER, $headers );
		
		$curl_resp = curl_exec( $curl_session );

		# parse response
		$header_size = curl_getinfo( $curl_session, CURLINFO_HEADER_SIZE );
		$resp_code = curl_getinfo( $curl_session, CURLINFO_HTTP_CODE );

		$resp_headers = trim(substr( $curl_resp, 0, $header_size ));
		$resp_body = trim( substr( $curl_resp, $header_size ) );
		
		# create response object:
		$response = new HttpResponse( $resp_code, $resp_body, $resp_headers );
		
		# handle error status codes
		switch ( $resp_code ) {
			case 400: throw new RestAuthBadRequest( $response );
			case 401: throw new RestAuthUnauthorized( $response );
			case 403: throw new RestAuthForbidden( $response );
			case 500: throw new RestAuthInternalServerError( $response );
		}

		
		curl_close( $curl_session );
		return $response;
	}

	/**
	 * Perform a GET request on the connection. This method takes care
	 * of escaping parameters and assembling the correct URL. This
	 * method internally calls the {@link RestAuthConnection::send() send}
	 * function to perform service authentication.
	 * 
	 * @param string $url The URL to perform the GET request on. The URL must
	 * 	not include a query string.
	 * @param array $params Optional query parameters for this request.
	 * @param array $headers Additional headers to send with this request.
	 *
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 * 	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	suffers from an internal error.
	 */
	public function get( $url, $params = array(), $headers = array() ) {
		$url = $this->sanitize_url( $url );

		if ( $params ) {
			$url .= '?' . http_build_query( $params );
		}

		return $this->send( 'GET', $url, NULL, $headers );
	}

	/**
	 * Perform a POST request on the connection. This method takes care
	 * of escaping parameters and assembling the correct URL. This
	 * method internally calls the {@link RestAuthConnection::send() send}
	 * function to perform service authentication.
	 * 
	 * @param string $url The URL to perform the GET request on. The URL must
	 * 	not include a query string.
	 * @param array $params Optional query parameters for this request.
	 * @param array $headers Additional headers to send with this request.
	 *
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 *	parsed. This should only happen with POST or PUT requests.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 * 	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	suffers from an internal error.
	 */
	public function post( $url, $params = array(), $headers = array() ) {
		$url = $this->sanitize_url( $url );

		$headers[] = 'Content-Type: application/json';
		$body = json_encode( $params );
		return $this->send( 'POST', $url, $body, $headers );
	}

	/**
	 * Perform a PUT request on the connection. This method takes care
	 * of escaping parameters and assembling the correct URL. This
	 * method internally calls the {@link RestAuthConnection::send() send}
	 * function to perform service authentication.
	 * 
	 * @param string $url The URL to perform the GET request on. The URL must
	 * 	not include a query string.
	 * @param array $params Optional query parameters for this request.
	 * @param array $headers Additional headers to send with this request.
	 *
	 * @throws BadRequest When the RestAuth service returns HTTP status code 400
	 * @throws InternalServerError When the RestAuth service returns HTTP status code 500
	 */
	public function put( $url, $params = array(), $headers = array() ) {
		$url = $this->sanitize_url( $url );

		$headers[] = 'Content-Type: application/json';
		$body = json_encode( $params );
		return $this->send( 'PUT', $url, $body, $headers );
	}

	/**
	 * Perform a DELETE request on the connection. This method takes care
	 * of escaping parameters and assembling the correct URL. This
	 * method internally calls the {@link RestAuthConnection::send() send}
	 * function to perform service authentication.
	 * 
	 * @param string $url The URL to perform the GET request on. The URL must
	 * 	not include a query string.
	 * @param array $headers Additional headers to send with this request.
	 *
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 * 	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	suffers from an internal error.
	 */
	public function delete( $url, $headers = array() ) {
		$url = $this->sanitize_url( $url );

		return $this->send( 'DELETE', $url, NULL, $headers );
	}
	
	/**
	 * Sanitize the path segment of an URL. Makes sure it ends with a slash,
	 * contains no double slashes and performs character escaping.
	 *
	 * @param string $url The path segment of an URL. Please note that this
	 * 	should not contain the query part ("?...") or the domain.
	 * @return string The sanitized path segmet of an URL
	 * @todo rename to sanitize_path
	 */
	public function sanitize_url( $url ) {
		if ( substr( $url, -1 ) !== '/' ) {
			$url .= '/';
		}

		$parts = array();
		foreach( explode( '/', $url ) as $part ) {
			$part = rawurlencode( $part );
			$parts[] = $part;
		}
		$url = implode( '/', $parts );

		return $url;
	}

}

/**
 * Superclass for {@link RestAuthUser} and {@link RestAuthGroup} objects.
 * Exists to wrap http requests with the prefix of the given resource.
 * 
 * @package php-restauth
 */
abstract class RestAuthResource {
	/**
	 * Perform a GET request on the connection that was passed via the constructor.
	 *
	 * This method prefixes the URL parameter with the 
	 * the resources class prefix ('/users/' or '/groups/') and passes
	 * all parameters (otherwise unmodified) to 
	 * {@link RestAuthConnection::get()}.
	 *
	 * @param string $url The URL to perform the GET request on. The URL must
	 * 	not include a query string.
	 * @param array $params Optional query parameters for this request.
	 * @param array $headers Additional headers to send with this request.
	 * @param string $prefix Modify the prefix used for this request
	 *
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 * 	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	suffers from an internal error.
	 */
	protected function _get( $url, $params = array(), $headers = array() ) {
		$url = static::prefix . $url;
		return $this->conn->get( $url, $params, $headers );
	}

	/**
	 * Perform a POST request on the connection that was passed via the constructor.
	 *
	 * This method prefixes the URL parameter with the 
	 * the resources class prefix ('/users/' or '/groups/') and passes
	 * all parameters (otherwise unmodified) to 
	 * {@link RestAuthConnection::post()}.
	 *
	 * @param string $url The URL to perform the POST request on. The URL must
	 * 	not include a query string.
	 * @param array $params Optional query parameters for this request.
	 * @param array $headers Additional headers to send with this request.
	 * @param string $prefix Modify the prefix used for this request
	 *
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 *	parsed. This should only happen with POST or PUT requests.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 * 	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	suffers from an internal error.
	 */
	protected function _post( $url, $params = array(), $headers = array() ) {
		$url = static::prefix . $url;
		return $this->conn->post( $url, $params, $headers );
	}

	/**
	 * Perform a PUT request on the connection that was passed via the constructor.
	 *
	 * This method prefixes the URL parameter with the 
	 * the resources class prefix ('/users/' or '/groups/') and passes
	 * all parameters (otherwise unmodified) to 
	 * {@link RestAuthConnection::put()}.
	 *
	 * @param string $url The URL to perform the PUT request on. The URL must
	 * 	not include a query string.
	 * @param array $params Optional query parameters for this request.
	 * @param array $headers Additional headers to send with this request.
	 * @param string $prefix Modify the prefix used for this request
	 *
	 * @throws {@link RestAuthBadRequest} When the request body could not be
	 *	parsed. This should only happen with POST or PUT requests.
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 * 	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	suffers from an internal error.
	 */
	protected function _put( $url, $params = array(), $headers = array() ) {
		$url = static::prefix . $url;
		return $this->conn->put( $url, $params, $headers );
	}

	/**
	 * Perform a DELETE request on the connection that was passed via the constructor.
	 *
	 * This method prefixes the URL parameter with the 
	 * the resources class prefix ('/users/' or '/groups/') and passes
	 * all parameters (otherwise unmodified) to 
	 * {@link RestAuthConnection::get()}.
	 *
	 * @param string $url The URL to perform the DELETE request on. The URL 
	 *	must not include a query string.
	 * @param array $headers Additional headers to send with this request.
	 * @param string $prefix Modify the prefix used for this request
	 *
	 * @throws {@link RestAuthUnauthorized} When service authentication
	 * 	failed.
	 * @throws {@link RestAuthForbidden} When service authentication failed
	 *	and authorization is not possible from this host.
	 * @throws {@link RestAuthInternalServerError} When the RestAuth service
	 *	suffers from an internal error.
	 */
	protected function _delete( $url, $headers = array() ) {
		return $this->conn->delete( static::prefix . $url, $headers );
	}
}
?>
