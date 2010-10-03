<?php

class HttpResponse {
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

	function set_credentials( $user, $password ) {
		$this->user = $user;
		$this->password = $password;
		$this->auth_field = $user . ':' . $password;
	}

	function send( $method, $url, $body = '', $headers = array() ) {
		# build final url
		$url = $this->base_url . $url;

		# prepare headers:
		$headers[] = 'Accept: application/json';
#		print( $method . ' ' . $url . "\n" );
#		if( $body ) print( "Body: $body\n" );
		
		# initialize curl session
#TODO: handle ssl
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

# TODO: catch 400, 401, 403, 500

		# parse response
		$header_size = curl_getinfo( $curl_session, CURLINFO_HEADER_SIZE );
		$resp_code = curl_getinfo( $curl_session, CURLINFO_HTTP_CODE );
		$resp_headers = trim(substr( $resp, 0, $header_size ));
		$resp_body = trim( substr( $resp, $header_size ) );
		
		curl_close( $curl_session );
		return new HttpResponse( $resp_code, $resp_body, $resp_headers );
	}

	function get( $url, $params = array(), $headers = array() ) {
		$url = $this->sanitize_url( $url );

		if ( $params ) {
			$url .= '?' . http_build_query( $params );
		}

		return $this->send( 'GET', $url, NULL, $headers );
	}

	function post( $url, $params = array(), $headers = array() ) {
		$url = $this->sanitize_url( $url );

		$headers[] = 'Content-Type: application/json';
		$body = json_encode( $params );
		return $this->send( 'POST', $url, $body, $headers );
	}

	function put( $url, $params = array(), $headers = array() ) {
		$url = $this->sanitize_url( $url );

		$headers[] = 'Content-Type: application/json';
		$body = json_encode( $params );
		return $this->send( 'PUT', $url, $body, $headers );
	}

	function delete( $url, $headers = array() ) {
		$url = $this->sanitize_url( $url );

		return $this->send( 'DELETE', $url, NULL, $headers );
	}
	
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

abstract class RestAuthResource {
	function get( $url, $params = array(), $headers = array(), $prefix = '' ) {
		if ( $prefix ) {
			$url = $prefix . $url;
		} else {
			$url = $this->prefix . $url;
		}

		return $this->conn->get( $url, $params, $headers );
	}

	function post( $url, $params = array(), $headers = array(), $prefix = '' ) {
		if ( $prefix ) {
			$url = $prefix . $url;
		} else {
			$url = $this->prefix . $url;
		}

		return $this->conn->post( $url, $params, $headers );
	}

	function put( $url, $params = array(), $headers = array(), $prefix = '' ) {
		if ( $prefix ) {
			$url = $prefix . $url;
		} else {
			$url = $this->prefix . $url;
		}

		return $this->conn->put( $url, $params, $headers );
	}

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
