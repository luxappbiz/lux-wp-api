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

add_action('wp_enqueue_scripts', function () {
  // Enqueue Bootstrap 4.6.2 CSS
  wp_enqueue_style(
    'bootstrap-css',
    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css',
    array(),
    '4.6.2',
    'all'
  );
  wp_enqueue_style(
    'lux-theme-css',
    get_template_directory_uri() . '/style.css',
    array(),
    '0.1.5',
    'all'
  );
  // Enqueue jQuery (required for Bootstrap JS, already included in WordPress)
  wp_enqueue_script('jquery');
  // Enqueue Bootstrap 4.6.2 JS ( Popper.js is a dependency for Bootstrap JS)
  wp_enqueue_script(
    'popper-js',
    'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js',
    array('jquery'),
    '2.11.8',
    true
  );
  wp_enqueue_script(
    'bootstrap-js',
    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js',
    array('jquery', 'popper-js'),
    '4.6.2',
    true
  );
});

$template_directory = get_template_directory();
// $get_template_directory_uri = get_template_directory_uri();

function lux_create_attachment($file, $url, $post_id = null) {
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

require_once( $template_directory . '/admin/push-notifications.php' );

function lux_rest_api_cors_headers() {
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

/*
 * Public
 */
require_once( $template_directory . '/public/woocommerce.php' );

require_once( $template_directory . '/public/shortcodes.php' );

require_once( $template_directory . '/public/membership.php' );

require_once( $template_directory . '/public/sellers.php' );

require_once( $template_directory . '/api/api.php' );

/**
 * Theme nav menus
 */
register_nav_menus( array(
  'header' => __( 'Header', 'lux' ),
  'footer' => __( 'Footer', 'lux' ),
  'primary' => __( 'Primary', 'lux' ),
) );

