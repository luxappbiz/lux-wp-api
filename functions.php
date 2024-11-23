<?php

add_action( 'wp_enqueue_scripts', function () {
  wp_enqueue_style( 'martfury-child-style', get_stylesheet_uri() );
  if ( is_rtl() ) {
    wp_enqueue_style( 'martfury-rtl', get_template_directory_uri() . '/rtl.css', array(), '20180105' );
  }
}, 20 );

$template_directory = get_template_directory();
// $get_template_directory_uri = get_template_directory_uri();

require_once( $template_directory . '/../lux-market-wp/api/products.php' );

// require_once( $template_directory . '/../lux-market-wp/api/vendors.php' );

require_once( $template_directory . '/../lux-market-wp/api/users.php' );

require_once( $template_directory . '/../lux-market-wp/api/mobile-api.php' );
