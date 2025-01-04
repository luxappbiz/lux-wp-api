<?php

add_action('rest_api_init', function () {
  register_rest_field(
    'user',
    'info',
    array(
      'get_callback' => function ($object, $field_name, $request) {
        $user_id = (int)$object['id'];
        $user = get_user_by('ID', $user_id);
        $banner_image = '';
        $phone = get_user_meta($user_id, 'phone', true);
        $dokan = get_user_meta($user_id, 'dokan_profile_settings', true);
        if ($dokan && isset($dokan) && isset($dokan['banner'])) {
          $banner_image = wp_get_attachment_url($dokan['banner']);
          if (!$phone) {
            $phone = $dokan['phone'];
            if (!$phone) {
              $dokan = get_user_meta($user_id, 'billing_phone', true);
            }
          }
        }
        return [
          'nicename' => $user->user_nicename,
          'display_name' => $user->display_name,
          'login' => $user->user_login,
          'email' => $user->user_email,
          'phone' => $phone,
          'banner_image' => $banner_image,
        ];
      },
      'update_callback' => null,
      'schema'          => null,
    )
  );
  // add_products_field_to_user_endpoint
  register_rest_field(
    'user', // Object type: this is the user endpoint.
    'products', // Name of the custom field.
    array(
      'get_callback' => function ($object, $field_name, $request) {
        $user_id = $object['id'];
        // Example: Get products associated with this user, assuming you have a custom way to link products to users.
        // Modify this query based on your specific setup.
        $args = array(
          'post_type'   => 'product',
          'author'      => $user_id,
          'post_status' => ['publish'],
          'numberposts' => -1, // Retrieve all products authored by this user.
        );
        $products = get_posts($args);
        // Format the products in the way you want them to appear in the API response.
        $product_data = array();
        foreach ($products as $product) {
          $wc_product = wc_get_product($product->ID);
          $title = get_the_title($product->ID);
          $gallery_image_ids = $wc_product->get_gallery_image_ids();
          $gallery_image_urls = array();
          $featured_image_id = get_post_thumbnail_id($product->ID);
          if ( $featured_image_id ) {
            $featured_image_url = wp_get_attachment_image_url( $featured_image_id, 'full' );
            $gallery_image_urls = array($featured_image_url);
          }
          foreach ( $gallery_image_ids as $image_id ) {
            $gallery_image_urls[] = wp_get_attachment_url( $image_id );
          }
          $product_data[] = array(
            'id'    => $product->ID,
            'title' => $title,
            'title_formatted' => html_entity_decode($title),
            'gallery_image_urls' => $gallery_image_urls,
            'link'  => get_permalink($product->ID),
            'price' => $wc_product->get_price(),
            // 'regular_price' => $wc_product->get_regular_price(),
            'sale_price' => $wc_product->get_sale_price(),
            'stock_status' => $wc_product->get_stock_status(),
          );
        }
        return $product_data;
      },
      'update_callback' => null,
      'schema'          => null,
    )
  );
});

/*
add_filter( 'rest_prepare_user', function ( $response, $post, $request ) {
  if (isset($response->data['_links'])) {
    unset( $response->data['_links'] );
  }
  return $response;
}, 10, 3 );
*/

/*
 * User - Data Deletion Requested At
 */
add_action('rest_api_init', function() {
  register_rest_field(
    'user',
    'data_deletion_requested_at',
    array(
      'get_callback' => function ($object, $field_name, $request) {
        $user_id = (int)$object['id'];
        // $user = get_user_by('ID', $user_id);
        return get_user_meta($user_id, 'user_requested_data_deletion', true);
      },
      'update_callback' => function ( $value, $object ) use ( $field ) {
        // Update the field/meta value.
        update_post_meta( $object->ID, $field, $value );
      },,
      'schema' => null,
    )
  );
});
