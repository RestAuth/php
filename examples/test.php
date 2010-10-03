<?php
require_once( 'restauth.php' );

$conn = new RestAuthConnection( 'localhost', 8000, 'vowi', 'vowi', false );

# verify initial state:
$all_users = RestAuthGetAllUsers( $conn );
if ( count( $all_users ) != 0 )
	die( "Error: ".count($all_users)." left over users!" );

$all_groups = RestAuthGetAllGroups( $conn );
if ( count( $all_groups ) != 0 )
	die( "Error: Left over groups!" );

# do some user testing:
$user0 = RestAuthCreateUser( $conn, 'user0', 'password0' );
$user1 = RestAuthCreateUser( $conn, 'user1', 'password1' );
$user2 = RestAuthCreateUser( $conn, 'user2', 'password2' );
$user3 = RestAuthCreateUser( $conn, 'user3', 'password3' );
$user4 = RestAuthCreateUser( $conn, 'user4', 'password4' );
$user5 = RestAuthCreateUser( $conn, 'user5', 'password5' );
$user6 = RestAuthCreateUser( $conn, 'user6', 'password6' );
$user7 = RestAuthCreateUser( $conn, 'user7', 'password7' );
$user8 = RestAuthCreateUser( $conn, 'user8', 'password8' );
$user9 = RestAuthCreateUser( $conn, 'user9', 'password9' );

# ten users?
$all_users = RestAuthGetAllUsers( $conn );
if ( count( $all_users ) != 10 )
	die( "Error: ".count($all_users)." users instead of 10!" );

foreach( $all_users as $user ) {
	# verify that we can get them all:
	RestAuthGetUser( $conn, $user->name );

	try {
		# verify that we can't create them:
		RestAuthCreateUser( $conn, $user->name, 'wrong password' );
		die( "Error: Successfully created existing user!" );
	} catch ( RestAuthUserExists $e ) {
		if ( $user->verify_password( 'wrong password' ) )
			die( "Error: Wrong password verified!" );

		$real_pass = str_replace( 'user', 'password', $user->name );
		if ( ! $user->verify_password( $real_pass ) )
			die( "Error: Real password could not be verified!" );
	}
}

# verify that getting a non-existing user throws an exception:
try {
	RestAuthGetUser( $conn, 'wrong_user' );
	die( "Error: Successfully got wrong user!" );
} catch ( RestAuthUserNotFound $e ) {
}

# update passwords, try to verify old/new ones
foreach( $all_users as $user ) {
	$orig_pass = str_replace( 'user', 'password', $user->name );
	$user->verify_password( $orig_pass );

	$user->set_password( "new $orig_pass" );
	if ( $user->verify_password( $orig_pass ) )
		die( "Error: Original password still verified!" );
	if ( ! $user->verify_password( "new $orig_pass" ) )
		die( "Error: New password doesn't verify!" );
}

function verify_property( $user, $key, $value ) {
	if ( $user->get_property( $key ) !== $value )
		die( "Error: $user->name property $key is wrong!" );

	$props = $user->get_properties();
	if ( $props[$key] !== $value )
		die( "Error: Received wrong value via get_properties()" );
}

# test user properties:
foreach( $all_users as $user ) {
	$props = $user->get_properties();
	if ( count($props) != 0 )
		die( "Error: Left over properties: ".count($props) );

	$user->create_property( 'name test', "name is $user->name" );
	verify_property( $user, 'name test', "name is $user->name" );

	# try to create it again:
	try {
		$user->create_property( 'name test', "wrong value" );
		die( "Error: Successfully created already existing property!" );
	} catch ( RestAuthPropertyExists $e ) {
	}

	# verify again:
	verify_property( $user, 'name test', "name is $user->name" );

	# next, we overwrite it with a new value and check that:
	$user->set_property( 'name test', "new property for $user->name" );
	verify_property( $user, 'name test', "new property for $user->name" );

	# next, we create a new value:
	$user->set_property( 'new property', "new property: $user->name" );
	verify_property( $user, 'new property', "new property: $user->name" );

	# get a completely non-existing property:
	try {
		$user->get_property( 'wrong property' );
		die( "Error: Successfully got a non-existing property!" );
	} catch ( RestAuthPropertyNotFound $e ) {
	}

	# delete a property and verify that its gone:
	try {
		$user->del_property( 'new property' );
		$props = $user->get_properties();
		if ( array_key_exists( 'new property', $props ) )
			die( "Error: Deleted property is still in get_properties()" );

		$user->get_property( 'new property' );
		die( "Error: Deleted property got by get_property()" );
	} catch ( RestAuthPropertyNotFound $e ) {
	}
}

$group0 = RestAuthCreateGroup( $conn, 'group 0' );
$group1 = RestAuthCreateGroup( $conn, 'group 1' );
$group2 = RestAuthCreateGroup( $conn, 'group 2' );
$group3 = RestAuthCreateGroup( $conn, 'group 3' );
$group4 = RestAuthCreateGroup( $conn, 'group 4' );
$group5 = RestAuthCreateGroup( $conn, 'group 5' );
$group6 = RestAuthCreateGroup( $conn, 'group 6' );
$group7 = RestAuthCreateGroup( $conn, 'group 7' );
$group8 = RestAuthCreateGroup( $conn, 'group 8' );
$group9 = RestAuthCreateGroup( $conn, 'group 9' );

$all_groups = RestAuthGetAllGroups( $conn );
if ( count( $all_groups ) != 10 )
	die( "Error: Not 10 groups!" );

foreach ( $all_groups as $group ) {
	# see if we can get them
	RestAuthGetGroup( $conn, $group->name );

	# verify that we have zero members:
	if ( count( $group->get_members() ) != 0 )
		die( "Error: Group seems to have some members?" );
}

function verify_membership( $group, $user ) {
	if ( ! $group->is_member( $user ) )
		die( "Error: $user->name is not a member of $group->name" );
	$members = $group->get_members();
	
	if ( ! in_array( $user, $members ) )
		die( "Error: $user->name not in all users of $group->name" );
}
# verify that we can get them all:
$group0->add_user( $user0 );
$group1->add_user( $user1 );
$group2->add_user( $user2 );
$group3->add_user( $user3 );
verify_membership( $group0, $user0 );
verify_membership( $group1, $user1 );
verify_membership( $group2, $user2 );
verify_membership( $group3, $user3 );

$group0->add_group( $group1 );
verify_membership( $group1, $user0 );

# cleanup:
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
