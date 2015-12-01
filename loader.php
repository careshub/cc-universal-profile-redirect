<?php
/*
Plugin Name: CC Universal Profile Redirect
Description: Adds generic URIs for user profile/current group redirects.
Version: 1.0.0
License: GPLv3
Author: David Cavins
*/

/**
 * Catch URIs that start with /my-profile/ and redirect to the user's profile.
 * Catch URIs that start with /current-hub/ and redirect to target within hub.
 * Send through the login screen if necessary.
 *
 * @package CC_Universal_Profile_Redirect
 * @since 1.0.0
 */

function cc_universal_profile_redirect_catch_uri() {

	// Catch URIs that start with /my-profile/ and redirect to the user's profile.
	if ( strpos( $_SERVER['REQUEST_URI'], '/my-profile/' ) === 0 ) {

		if ( $current_user_id = get_current_user_id() ) {
			// For logged-in users, we replace '/myprofile/' and send them to their user domain.
			$target = bp_loggedin_user_domain() . str_replace( '/my-profile/', '', $_SERVER['REQUEST_URI'] );
		} else {
			// If the user is not logged in, we send them to login screen
			// with the redirect args set to return them here. Then, case #1 kicks in.
			$target = wp_login_url( ( is_ssl() ? 'https://' : 'http://' ) .  $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'] );
		}

		wp_safe_redirect( $target );
		exit;

	}

	// Catch URIs that start with /current-hub/ and redirect to target within hub.
	if ( strpos( $_SERVER['REQUEST_URI'], '/current-hub/' ) === 0 ) {
		/** This request MUST originate in the target group, since "current group"
		 *  has no meaning outside of a group.
		 */
		$referrer = wp_get_referer();
		$referrer_parts = parse_url( $referrer );
		$path_parts = array_values( array_filter( explode( '/', $referrer_parts['path'] ) ) );

		if ( bp_get_groups_slug() == $path_parts[0] ) {
			// This request originated in a group, so we can do something with it.

			// Remove the 'groups' leading path parts element.
			unset( $path_parts[0] );
			// Get the ID from the slug, but the group may be nested:
			// groups/parent-group/child-group/grandchild-group/random-tab-slug
			// We work through the array of path parts until groups_get_id returns 0.
			$group_ids = array();
			foreach ( $path_parts as $key => $slug ) {
				if ( $group_id = groups_get_id( $slug ) ) {
					// Add the group id to the array of ids.
					$group_ids[$key] = $group_id;
				} else {
					// This path part isn't a group slug; we can stop looking.
					break;
				}
			}

			// The last element in the array is the id of the group where this request originated.
			$group_id = array_pop( $group_ids );

			$group_object = groups_get_group( array( 'group_id' => $group_id ) );
			$target = bp_get_group_permalink( $group_object ) . str_replace( '/current-hub/', '', $_SERVER['REQUEST_URI'] );

			wp_safe_redirect( $target );
			exit;
		}

	}

}
add_action( 'bp_init', 'cc_universal_profile_redirect_catch_uri', 9 );
