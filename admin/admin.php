<?php

// $template_directory = get_template_directory();

/**
 * Login - Assets.
 */
add_action('login_enqueue_scripts', function () {
  wp_enqueue_style( 'admin-login', get_template_directory_uri() . '/../lux-wp-api/assets/styles/admin-login.css', false, null );
});

// Replace WordPress.org login link
add_filter( 'login_headerurl', function () {
  return home_url('/'); // Replace with your desired URL
} );

// Update login header text
add_filter( 'login_headertext', function () {
  return get_bloginfo('name'); // Replace with your desired site name
} );

// Set the 'from' email address
add_filter( 'wp_mail_from', function ( $original_email_address ) {
  return 'info@gnetworkclub.com';
} );
