<?php
require_once( 'RestAuth/restauth.php' );

try {
    // ... some restauth related code...
} catch (RestAuthUnauthorized $e) {
    // You try to use an invalid service username/password
} catch (RestAuthBadResponse $e) {
    // usually indicates a buggy implemenation
} catch (RestAuthException $e) {
    // catch all!
}

?>