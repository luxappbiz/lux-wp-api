<?php

/*
 * Remove martfury child theme enqueue
add_action( 'wp_enqueue_scripts', function () {
  wp_enqueue_style( 'martfury-child-style', get_stylesheet_uri() );
  if ( is_rtl() ) {
    wp_enqueue_style( 'martfury-rtl', get_template_directory_uri() . '/rtl.css', array(), '20180105' );
  }
}, 20 );
*/

$template_directory = get_template_directory();
// $get_template_directory_uri = get_template_directory_uri();

/*
 * Admin
 */

require_once( $template_directory . '/../lux-wp-api/admin/admin.php' );

require_once( $template_directory . '/../lux-wp-api/admin/messages.php' );

/*
 * API
 */
require_once( $template_directory . '/../lux-wp-api/api/products.php' );

// require_once( $template_directory . '/../lux-wp-api/api/vendors.php' );

require_once( $template_directory . '/../lux-wp-api/api/users.php' );

require_once( $template_directory . '/../lux-wp-api/api/mobile-api.php' );

require_once( $template_directory . '/../lux-wp-api/api/favorites.php' );

require_once( $template_directory . '/../lux-wp-api/api/conversations.php' );

require_once( $template_directory . '/../lux-wp-api/api/sellers.php' );
