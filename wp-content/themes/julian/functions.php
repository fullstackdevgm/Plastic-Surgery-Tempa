<?php

/*-----------------------------------------------------------------------------------*/
/* Start LimeCuda Child Theme Functions */
/*-----------------------------------------------------------------------------------*/




function all_settings_page() {
    add_options_page( __( 'All Settings' ), __( 'All Settings' ), 'administrator', 'options.php' );
}
add_action( 'admin_menu', 'all_settings_page' );

remove_action('wp_head', 'wp_generator');

add_filter('login_errors',create_function('$a', "return null;"));


function new_contactmethods( $contactmethods ) {
  $contactmethods['twitter'] = 'Twitter'; // Add Twitter
  $contactmethods['facebook'] = 'Facebook'; // Add Facebook
  unset($contactmethods['jabber']); // Remove Jabber

return $contactmethods;
}

add_filter('user_contactmethods','new_contactmethods',10,1);


function fb_change_mce_options($initArray) {
	$ext = 'pre[id|name|class|style],iframe[align|longdesc| name|width|height|frameborder|scrolling|marginheight| marginwidth|src]';

	if ( isset( $initArray['extended_valid_elements'] ) ) {
		$initArray['extended_valid_elements'] .= ',' . $ext;
	} else {
		$initArray['extended_valid_elements'] = $ext;
	}

	return $initArray;
}
add_filter('tiny_mce_before_init', 'fb_change_mce_options');


add_filter( 'auth_cookie_expiration', 'keep_me_logged_in_for_2_days' );
function keep_me_logged_in_for_2_days( $expirein ) {
    return 172800; // 2 days in seconds
}

add_image_size( 'before-after', 277, 346, true );



/*-----------------------------------------------------------------------------------*/
/* Don't add any code below here or the sky will fall down */
/*-----------------------------------------------------------------------------------*/
?>