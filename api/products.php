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
