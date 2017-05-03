<?php
/**
 * @original Plugin Name: JSON Basic Authentication
 * @original Description: Basic Authentication handler for the JSON API, used for development and debugging purposes
 * @original Author: WordPress API Team
 * @original Author URI: https://github.com/WP-API
 * @original Version: 0.1
 * @original Plugin URI: https://github.com/WP-API/Basic-Auth
 */

function dt_json_basic_auth_handler( $user ) {
	global $wp_dt_json_basic_auth_error;

	$wp_dt_json_basic_auth_error = null;

	// Don't authenticate twice
	if ( ! empty( $user ) ) {
		return $user;
	}

	// Check that we're trying to authenticate
	if ( !isset( $_SERVER['PHP_AUTH_USER'] ) ) {
		return $user;
	}

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	/**
	 * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
	 * get_currentuserinfo which in turn calls the determine_current_user filter. This leads to infinite
	 * recursion and a stack overflow unless the current function is removed from the determine_current_user
	 * filter during authentication.
	 */
	remove_filter( 'determine_current_user', 'dt_json_basic_auth_handler', 20 );

	$user = wp_authenticate( $username, $password );

	add_filter( 'determine_current_user', 'dt_json_basic_auth_handler', 20 );

	if ( is_wp_error( $user ) ) {
		$wp_dt_json_basic_auth_error = $user;
		return null;
	}

	$wp_dt_json_basic_auth_error = true;

	return $user->ID;
}
add_filter( 'determine_current_user', 'dt_json_basic_auth_handler', 20 );

function dt_json_basic_auth_error( $error ) {
	// Passthrough other errors
	if ( ! empty( $error ) ) {
		return $error;
	}

	global $wp_dt_json_basic_auth_error;

	return $wp_dt_json_basic_auth_error;
}
add_filter( 'json_authentication_errors', 'dt_json_basic_auth_error' );
