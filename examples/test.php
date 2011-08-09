<?php
require_once('RestAuth/restauth.php');

$conn = new RestAuthConnection('http://[::1]:8000', 'vowi', 'vowi');

// verify initial state:
$all_users = RestAuthUser::get_all($conn);
if (count($all_users) != 0)
    die("Error: ".count($all_users)." left over users!");

$all_groups = RestAuthGroup::get_all($conn);
if (count($all_groups) != 0)
    die("Error: Left over groups!");

// do some user testing:
$user0 = RestAuthUser::create($conn, 'user0', 'password0');
$user1 = RestAuthUser::create($conn, 'user1', 'password1');
$user2 = RestAuthUser::create($conn, 'user2', 'password2');
$user3 = RestAuthUser::create($conn, 'user3', 'password3');
$user4 = RestAuthUser::create($conn, 'user4', 'password4');
$user5 = RestAuthUser::create($conn, 'user5', 'password5');
$user6 = RestAuthUser::create($conn, 'user6', 'password6');
$user7 = RestAuthUser::create($conn, 'user7', 'password7');
$user8 = RestAuthUser::create($conn, 'user8', 'password8');
$user9 = RestAuthUser::create($conn, 'user9', 'password9');

// ten users?
$all_users = RestAuthUser::get_all($conn);
if (count($all_users) != 10)
    die("Error: ".count($all_users)." users instead of 10!");

foreach ($all_users as $user) {
    // verify that we can get them all:
    RestAuthUser::get($conn, $user->name);

    try {
        // verify that we can't create them:
        RestAuthUser::create($conn, $user->name, 'wrong password');
        die("Error: Successfully created existing user!");
    } catch (RestAuthUserExists $e) {
        if ($user->verifyPassword('wrong password'))
            die("Error: Wrong password verified!");

        $real_pass = str_replace('user', 'password', $user->name);
        if (! $user->verifyPassword($real_pass))
            die("Error: Real password could not be verified!");
    }
}

// verify that getting a non-existing user throws an exception:
try {
    RestAuthUser::get($conn, 'wrong_user');
    die("Error: Successfully got wrong user!");
} catch (RestAuthResourceNotFound $e) {
}

// update passwords, try to verify old/new ones
foreach ($all_users as $user) {
    $orig_pass = str_replace('user', 'password', $user->name);
    $user->verifyPassword($orig_pass);

    $user->setPassword("new $orig_pass");
    if ($user->verifyPassword($orig_pass))
        die("Error: Original password still verified!");
    if (! $user->verifyPassword("new $orig_pass"))
        die("Error: New password doesn't verify!");
}

function verifyProperty($user, $key, $value)
{
    if ($user->get_property($key) !== $value)
        die("Error: $user->name property $key is wrong: '$value'/'$recv_value'\n");

    $props = $user->get_properties();
    if ($props[$key] !== $value)
        die("Error: Received wrong value via get_properties()");
}

// test user properties:
foreach ($all_users as $user) {
    $props = $user->get_properties();
    if (count($props) != 0)
        die("Error: Left over properties: ".count($props));

    $user->create_property('name test', "name is $user->name");
    verifyProperty($user, 'name test', "name is $user->name");

    // try to create it again:
    try {
        $user->create_property('name test', "wrong value");
        die("Error: Successfully created already existing property!");
    } catch (RestAuthPropertyExists $e) {
    }

    // verify again:
    verifyProperty($user, 'name test', "name is $user->name");

    // next, we overwrite it with a new value and check that:
    $user->set_property('name test', "new property for $user->name");
    verifyProperty($user, 'name test', "new property for $user->name");

    // next, we create a new value:
    $user->set_property('new property', "new property: $user->name");
    verifyProperty($user, 'new property', "new property: $user->name");

    // get a completely non-existing property:
    try {
        $user->get_property('wrong property');
        die("Error: Successfully got a non-existing property!");
    } catch (RestAuthResourceNotFound $e) {
    }

    // delete a property and verify that its gone:
    try {
        $user->remove_property('new property');
        $props = $user->get_properties();
        if (array_key_exists('new property', $props))
            die("Error: Deleted property is still in get_properties()");

        $user->get_property('new property');
        die("Error: Deleted property got by get_property()");
    } catch (RestAuthResourceNotFound $e) {
    }
}

$group0 = RestAuthGroup::create($conn, 'group 0');
$group1 = RestAuthGroup::create($conn, 'group 1');
$group2 = RestAuthGroup::create($conn, 'group 2');
$group3 = RestAuthGroup::create($conn, 'group 3');
$group4 = RestAuthGroup::create($conn, 'group 4');
$group5 = RestAuthGroup::create($conn, 'group 5');
$group6 = RestAuthGroup::create($conn, 'group 6');
$group7 = RestAuthGroup::create($conn, 'group 7');
$group8 = RestAuthGroup::create($conn, 'group 8');
$group9 = RestAuthGroup::create($conn, 'group 9');

$all_groups = RestAuthGroup::get_all($conn);
if (count($all_groups) != 10)
    die("Error: Not 10 groups!");

foreach ($all_groups as $group) {
    // see if we can get them
    RestAuthGroup::get($conn, $group->name);

    // verify that we have zero members:
    if (count($group->get_members()) != 0)
        die("Error: Group seems to have some members?");
}

function verify_membership($group, $user)
{
    if (! $group->is_member($user))
        die("Error: $user->name is not a member of $group->name");
    $members = $group->get_members();
    
    if (! in_array($user, $members))
        die("Error: $user->name not in all users of $group->name");
}
// verify that we can get them all:
$group0->add_user($user0);
$group1->add_user($user1);
$group2->add_user($user2);
$group3->add_user($user3);
verify_membership($group0, $user0);
verify_membership($group1, $user1);
verify_membership($group2, $user2);
verify_membership($group3, $user3);

$group0->add_group($group1);
verify_membership($group1, $user0);

// cleanup:
$user0->remove();
$user1->remove();
$user2->remove();
$user3->remove();
$user4->remove();
$user5->remove();
$user6->remove();
$user7->remove();
$user8->remove();
$user9->remove();

$group0->remove();
$group1->remove();
$group2->remove();
$group3->remove();
$group4->remove();
$group5->remove();
$group6->remove();
$group7->remove();
$group8->remove();
$group9->remove();

exit();
?>
