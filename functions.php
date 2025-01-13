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
require_once( $template_directory . '/admin/admin.php' );

require_once( $template_directory . '/admin/messages.php' );


// add_filter('rest_allowed_cors_headers', function ($headers) {
//   $headers['Access-Control-Allow-Origin'] = '*';
//   $headers['Access-Control-Allow-Methods'] = 'GET, POST, PUT, DELETE, OPTIONS';
//   $headers['Access-Control-Allow-Credentials'] = 'true';
//   $headers['Access-Control-Allow-Headers'] = 'Authorization, Content-Type, X-WP-Nonce';
//   $headers['AB-TEST'] = 'TEST';
//   return $headers;
// }, 200);
// add_filter('rest_exposed_cors_headers', function ($headers) {
//   $headers['Access-Control-Allow-Origin'] = '*';
//   $headers['Access-Control-Allow-Methods'] = 'GET, POST, PUT, DELETE, OPTIONS';
//   $headers['Access-Control-Allow-Credentials'] = 'true';
//   $headers['Access-Control-Allow-Headers'] = 'Authorization, Content-Type, X-WP-Nonce';
//   $headers['AB-TEST'] = 'TEST';
//   return $headers;
// }, 200);

function custom_rest_api_cors_headers() {
  // Allow all origins or specify your domain
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
  header('Access-Control-Allow-Credentials: true');
  header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');

  // Handle preflight OPTIONS request
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      header('HTTP/1.1 200 OK');
      exit;
  }
}
add_action('rest_api_init', function () {
  add_action('rest_pre_serve_request', 'custom_rest_api_cors_headers');
});


/*
 * API
 */
require_once( $template_directory . '/api/products.php' );

// require_once( $template_directory . '/api/vendors.php' );

require_once( $template_directory . '/api/users.php' );

require_once( $template_directory . '/api/mobile-api.php' );

require_once( $template_directory . '/api/favorites.php' );

require_once( $template_directory . '/api/messages.php' );

require_once( $template_directory . '/api/sellers.php' );

/**
 * Theme nav menus
 */
register_nav_menus( array(
  'header' => __( 'Header', 'lux' ),
  'footer' => __( 'Footer', 'lux' ),
  'primary' => __( 'Primary', 'lux' ),
) );

/*
 * Public
 */
require_once( $template_directory . '/public/woocommerce.php' );
