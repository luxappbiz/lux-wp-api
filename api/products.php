<?php

// Add the custom field to the WooCommerce REST API product response
add_action('rest_api_init', function () {
  register_rest_field('product',
    'product',
    array(
      'get_callback' => function ($object, $field_name, $request) {
        $product = wc_get_product($object['id']);
        // return wc_get_product($object['id']);
        return [
          'price' => $product->get_price(),
          'regular_price' => $product->get_regular_price(),
          'sale_price' => $product->get_sale_price(),
          // 'date_on_sale_from' => $product->get_date_on_sale_from(),
          // 'date_on_sale_to' => $product->get_date_on_sale_to(),
          // 'total_sales' => $product->get_total_sales(),
          // 'gallery_image_ids' => $product->get_gallery_image_ids(),
        ];
      },
      'update_callback' => null,
      'schema' => null,
    )
  );
  register_rest_field('product',
    'images',
    array(
      'get_callback' => function ( $object ) {
        $product = wc_get_product( $object['id'] );
        $gallery_image_ids = $product->get_gallery_image_ids();
        $gallery_image_urls = array();
        $featured_image_id = get_post_thumbnail_id( $object['id'] );
        if ( $featured_image_id ) {
          $featured_image_url = wp_get_attachment_image_url( $featured_image_id, 'full' );
          $gallery_image_urls = array($featured_image_url);
        }
        foreach ( $gallery_image_ids as $image_id ) {
          $gallery_image_urls[] = wp_get_attachment_url( $image_id );
        }
        return $gallery_image_urls;
      },
      'update_callback' => null,
      'schema' => null,
    )
  );
  register_rest_field('product',
    'title_formatted',
    array(
      'get_callback' => function ( $object ) {
        $title = get_the_title( $object['id'] );
        $title_formatted = html_entity_decode($title);
        return $title_formatted;
      },
      'update_callback' => null,
      'schema' => null,
    )
  );
  register_rest_field('product',
    'excerpt_unformatted',
    array(
      'get_callback' => function ( $object ) {
        $product = wc_get_product( $object['id'] );
        $excerpt = $product->get_short_description();
        // Strip all HTML tags
        $plain_excerpt = wp_strip_all_tags( $excerpt );
        $plain_excerpt = preg_replace('/\s+/', ' ', $plain_excerpt);
        return $plain_excerpt;
      },
      'update_callback' => null,
      'schema' => null,
    )
  );
  if (function_exists( 'dokan_get_vendor_by_product' )) {
    register_rest_field('product',
      'vendor',
      array(
        'get_callback' => function ( $object ) {
          $vendor = dokan_get_vendor_by_product( $object['id'] );
          if ( ! $vendor ) {
            return false; // Vendor not found
          }          
          return $vendor->get_id();
        },
        'update_callback' => null,
        'schema' => null,
      )
    );
  }

  register_rest_route('lux/v1', '/create-product', array(
    'methods'  => 'POST',
    'callback' => 'lux_create_product_with_image',
    'permission_callback' => '__return_true',
  ));
});

add_filter( 'rest_prepare_product', function ( $response, $post, $request ) {
  if (isset($response->data['_links'])) {
    unset( $response->data['_links'] );
  }
  if (isset($response->data['featured_media'])) {
    unset( $response->data['featured_media'] );
  }
  if (isset($response->data['ping_status'])) {
    unset( $response->data['ping_status'] );
  }
  if (isset($response->data['meta'])) {
    unset( $response->data['meta'] );
  }
  if (isset($response->data['template'])) {
    unset( $response->data['template'] );
  }
  if (isset($response->data['class_list'])) {
    unset( $response->data['class_list'] );
  }
  return $response;
}, 10, 3 );

/*
add_action( 'rest_api_init', function () {
  register_rest_field( 'product', // The custom post type slug
    'featured_image', // The name of the custom field
    array(
      'get_callback'    => function ( $object ) {
        $featured_image_id = get_post_thumbnail_id( $object['id'] );
        if ( $featured_image_id ) {
          $featured_image_url = wp_get_attachment_image_url( $featured_image_id, 'full' );
          return $featured_image_url;
        }
        return false;
      },
      'update_callback' => null,
      'schema'          => null,
    )
  );
} );
*/

function lux_create_product_with_image(WP_REST_Request $request) {
  // Ensure WooCommerce is active
  if (!class_exists('WC_Product')) {
      return new WP_Error('woocommerce_not_active', 'WooCommerce is not active', array('status' => 500));
  }
  // Get parameters
  $title = sanitize_text_field($request->get_param('title'));
  $price = sanitize_text_field($request->get_param('regular_price'));
  $image = $request->get_file_params()['thumbnail'];
  return new WP_REST_Response([
    'title'       => $title,
    'price'    => $price,
    'image' => $image,
  ], 200);
  if (!$title || !$price || !$image) {
    return new WP_Error('missing_params', 'Missing required parameters: title, price, or image', array('status' => 400));
  }
  // Handle image upload
  $upload = wp_handle_upload($image, array('test_form' => false));
  if (isset($upload['error'])) {
    return new WP_Error('image_upload_failed', $upload['error'], array('status' => 500));
  }
  // Create attachment
  $attachment_id = lux_create_attachment($upload['file'], $upload['url']);
  if (!$attachment_id) {
    return new WP_Error('attachment_creation_failed', 'Failed to create attachment', array('status' => 500));
  }
  // Create WooCommerce product
  $product = new WC_Product_Simple();
  $product->set_name($title);
  $product->set_regular_price($price);
  $product->set_image_id($attachment_id);
  $product_id = $product->save();
  if (!$product_id) {
    return new WP_Error('product_creation_failed', 'Failed to create product', array('status' => 500));
  }
  // Return product info
  return new WP_REST_Response([
    'id'       => $product_id,
    'title'    => $title,
    'price'    => $price,
    'image_id' => $attachment_id,
  ], 200);
}

function lux_create_attachment($file, $url) {
  $filetype = wp_check_filetype($file);
  $attachment = array(
    'guid'           => $url,
    'post_mime_type' => $filetype['type'],
    'post_title'     => sanitize_file_name(basename($file)),
    'post_content'   => '',
    'post_status'    => 'inherit',
  );
  $attachment_id = wp_insert_attachment($attachment, $file);
  require_once(ABSPATH . 'wp-admin/includes/image.php');
  $attach_data = wp_generate_attachment_metadata($attachment_id, $file);
  wp_update_attachment_metadata($attachment_id, $attach_data);
  return $attachment_id;
}
