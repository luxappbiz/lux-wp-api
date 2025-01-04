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

function lux_create_attachment($file, $url, $post_id = null)
{
  $filetype = wp_check_filetype($file);
  $attachment = array(
    'guid'           => $url,
    'post_mime_type' => $filetype['type'],
    'post_title'     => sanitize_file_name(basename($file)),
    'post_content'   => '',
    'post_status'    => 'inherit',
  );
  $attachment_id = wp_insert_attachment($attachment, $file, $post_id);
  require_once(ABSPATH . 'wp-admin/includes/image.php');
  $attach_data = wp_generate_attachment_metadata($attachment_id, $file);
  wp_update_attachment_metadata($attachment_id, $attach_data);
  return $attachment_id;
}

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

require_once( $template_directory . '/../lux-wp-api/api/messages.php' );

require_once( $template_directory . '/../lux-wp-api/api/sellers.php' );

/**
 * Theme nav menus
 */
register_nav_menus( array(
  'header' => __( 'Header', 'lux' ),
  'footer' => __( 'Footer', 'lux' ),
  'primary' => __( 'Primary', 'lux' ),
) );
