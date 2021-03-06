<?php

// NOTE: that all error handling in this example is skipped for the sake of
// clarity.

require_once('RestAuth/restauth.php');

$conn = new RestAuthConnection('http://[::1]:8000', 'vowi', 'vowi');

// create a *new* user named 'foobar', and do some interesting things:
$user = RestAuthUser::create($conn, 'foobar', 'somepassword');
if ($user->verifyPassword('somepassword')) {
    print("User has password 'somepassword'.\n");
} else {
    print("User seems to have a different password.\n");
}

// set a property of that user:
$user->setProperty('mykey', 'myvalue');

// retreive a single property:
print("property mykey has value '". $user->getProperty('mykey') . "'\n");

// retreive all properties:
$props = $user->getProperties();
print("... same when retreiving all properties: '" . $props['mykey'] . "'\n");

// If performance is critical, do not use the factory methods to get user
// objects, instead reference them directly:
$user = new RestAuthUser($conn, 'foobar');
// there is no guarantee here that this object exists:
$user->verifyPassword('somepassword'); 

// Groups work in much the same way as users:
RestAuthGroup::create($conn, 'groupname'); // first create it...

// get verifies that the group exists:
$group = RestAuthGroup::get($conn, 'groupname'); 
$group->addUser($user); // may also just be the username!
$members = $group->getMembers(); // returns a list of RestAuthUser instances
print("Users: " . implode(', ', $members) . "\n"); 

// finally, remove group and user so you can call this script multiple times
$user->remove();
$group->remove();

?>