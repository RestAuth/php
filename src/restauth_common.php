<?php

/**
 * A standard HTTP response that also parses headers and makes them conveniently
 * available as an array.
 */
class HttpResponse {
	/**
	 * Constructor. 
	 *
	 * @param int $code The HTTP response code.
	 * @param str $body The body of the HTTP response.
	 * @param str $headers The headers of the HTTP response.
	 */
	function __construct( $code, $body, $headers ) {
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
 * {@link RestAuthConnection} or their respective factory methods.
 *
 * Note that instantiating an object of this class does not invoke any network
 * connection by itself. Due to the statelessnes nature of HTTP i.e. an
 * unavailable service will only trigger an error when actually doing a request.
 */
class RestAuthConnection {
	function __construct( $host, $port, $user, $password, $use_ssl = true, $cert = '' ) {
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
	 * @param str $user The username to use
	 * @param str $password The password to use
	 */
	function set_credentials( $user, $password ) {
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
	 * @todo Handle SSL
	 * @todo Handl general response codes (400, 401, 403, 500)
	 */
	function send( $method, $url, $body = '', $headers = array() ) {
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
		
		$resp = curl_exec( $curl_session );

		# parse response
		$header_size = curl_getinfo( $curl_session, CURLINFO_HEADER_SIZE );
		$resp_code = curl_getinfo( $curl_session, CURLINFO_HTTP_CODE );
		$resp_headers = trim(substr( $resp, 0, $header_size ));
		$resp_body = trim( substr( $resp, $header_size ) );
		
		curl_close( $curl_session );
		return new HttpResponse( $resp_code, $resp_body, $resp_headers );
	}

	/**
	 * Perform a GET request on the connection. This method takes care
	 * of escaping parameters and assembling the correct URL. This
	 * method internally calls the {@link RestAuthConnection::send() send}
	 * function to perform service authentication.
	 * 
	 * @param str $url The URL to perform the GET request on. The URL must
	 * 	not include a query string.
	 * @param array $params Optional query parameters for this request.
	 * @param array $headers Additional headers to send with this request.
	 *
	 * @throws BadRequest When the RestAuth service returns HTTP status code 400
	 * @throws InternalServerError When the RestAuth service returns HTTP status code 500
	 */
	function get( $url, $params = array(), $headers = array() ) {
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
	 * @param str $url The URL to perform the GET request on. The URL must
	 * 	not include a query string.
	 * @param array $params Optional query parameters for this request.
	 * @param array $headers Additional headers to send with this request.
	 *
	 * @throws BadRequest When the RestAuth service returns HTTP status code 400
	 * @throws InternalServerError When the RestAuth service returns HTTP status code 500
	 */
	function post( $url, $params = array(), $headers = array() ) {
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
	 * @param str $url The URL to perform the GET request on. The URL must
	 * 	not include a query string.
	 * @param array $params Optional query parameters for this request.
	 * @param array $headers Additional headers to send with this request.
	 *
	 * @throws BadRequest When the RestAuth service returns HTTP status code 400
	 * @throws InternalServerError When the RestAuth service returns HTTP status code 500
	 */
	function put( $url, $params = array(), $headers = array() ) {
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
	 * @param str $url The URL to perform the GET request on. The URL must
	 * 	not include a query string.
	 * @param array $headers Additional headers to send with this request.
	 *
	 * @throws BadRequest When the RestAuth service returns HTTP status code 400
	 * @throws InternalServerError When the RestAuth service returns HTTP status code 500
	 */
	function delete( $url, $headers = array() ) {
		$url = $this->sanitize_url( $url );

		return $this->send( 'DELETE', $url, NULL, $headers );
	}
	
	/**
	 * Sanitize the path segment of an URL. Makes sure it ends with a slash,
	 * contains no double slashes and performs character escaping.
	 *
	 * @param str $url The path segment of an URL. Please note that this
	 * 	should not contain the query part ("?...") or the domain.
	 * @return str The sanitized path segmet of an URL
	 * @todo: rename to sanitize_path
	 */
	function sanitize_url( $url ) {
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
	 * @param str $url The URL to perform the GET request on. The URL must
	 * 	not include a query string.
	 * @param array $params Optional query parameters for this request.
	 * @param array $headers Additional headers to send with this request.
	 * @param str $prefix Modify the prefix used for this request
	 *
	 * @throws BadRequest When the RestAuth service returns HTTP status code 400
	 * @throws InternalServerError When the RestAuth service returns HTTP status code 500
	 */
	 */
	function get( $url, $params = array(), $headers = array(), $prefix = '' ) {
		if ( $prefix ) {
			$url = $prefix . $url;
		} else {
			$url = $this->prefix . $url;
		}

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
	 * @param str $url The URL to perform the POST request on. The URL must
	 * 	not include a query string.
	 * @param array $params Optional query parameters for this request.
	 * @param array $headers Additional headers to send with this request.
	 * @param str $prefix Modify the prefix used for this request
	 *
	 * @throws BadRequest When the RestAuth service returns HTTP status code 400
	 * @throws InternalServerError When the RestAuth service returns HTTP status code 500
	 */
	function post( $url, $params = array(), $headers = array(), $prefix = '' ) {
		if ( $prefix ) {
			$url = $prefix . $url;
		} else {
			$url = $this->prefix . $url;
		}

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
	 * @param str $url The URL to perform the PUT request on. The URL must
	 * 	not include a query string.
	 * @param array $params Optional query parameters for this request.
	 * @param array $headers Additional headers to send with this request.
	 * @param str $prefix Modify the prefix used for this request
	 *
	 * @throws BadRequest When the RestAuth service returns HTTP status code 400
	 * @throws InternalServerError When the RestAuth service returns HTTP status code 500
	 */
	function put( $url, $params = array(), $headers = array(), $prefix = '' ) {
		if ( $prefix ) {
			$url = $prefix . $url;
		} else {
			$url = $this->prefix . $url;
		}

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
	 * @param str $url The URL to perform the DELETE request on. The URL 
	 *	must not include a query string.
	 * @param array $headers Additional headers to send with this request.
	 * @param str $prefix Modify the prefix used for this request
	 *
	 * @throws BadRequest When the RestAuth service returns HTTP status code 400
	 * @throws InternalServerError When the RestAuth service returns HTTP status code 500
	 */
	function delete( $url, $headers = array(), $prefix = '' ) {
		if ( $prefix ) {
			$url = $prefix . $url;
		} else {
			$url = $this->prefix . $url;
		}

		return $this->conn->delete( $url, $headers );
	}
}
?>
