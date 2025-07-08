<?php

// Add the custom field to the WooCommerce REST API product response
add_action('rest_api_init', function () {
  register_rest_field('product', 'author', [
    'get_callback' => function ($product) {
      $product_id = $product['id'];
      $post = get_post($product_id);
      $author_id = $post->post_author;
      $author_name = get_the_author_meta('display_name', $author_id);
      $metronet_image_id = get_user_option('metronet_image_id', $author_id);
      return [
        'id' => $author_id,
        'name' => $author_name,
        'image_url' => wp_get_attachment_url($metronet_image_id),
      ];
    },
    'schema' => [
      'description' => __('Author of the product.'),
      'type' => 'object',
      'context' => ['view', 'edit'],
      'properties' => [
        'id' => [
          'type' => 'integer',
          'description' => __('Author ID.'),
        ],
        'name' => [
          'type' => 'string',
          'description' => __('Author name.'),
        ],
      ],
    ],
  ]);

  register_rest_field(
    'product',
    'product',
    array(
      'get_callback' => function ($object, $field_name, $request) {
        $product = wc_get_product($object['id']);
        // return wc_get_product($object['id']);
        return [
          'price' => $product->get_price(),
          'regular_price' => $product->get_regular_price(),
          'sale_price' => $product->get_sale_price(),
          'mileage' => get_post_meta($object['id'], 'mileage', true),
          'horsepower' => get_post_meta($object['id'], 'power_hp', true),
          'interiorColor' => get_post_meta($object['id'], 'interior', true),
          'exteriorColor' => get_post_meta($object['id'], 'exterior', true),
          'year' => get_post_meta($object['id'], 'year', true),
          'make' => get_post_meta($object['id'], 'make', true),
          'model' => get_post_meta($object['id'], 'model', true),
          'transmissionType' => get_post_meta($object['id'], 'transmission', true),
        ];
      },
      'update_callback' => function ($value, $object, $field_name, $request) {
        $product_id = $object->ID;

        if ($request->get_param('mileage') !== null) {
          update_post_meta($product_id, 'mileage', sanitize_text_field($request->get_param('mileage')));
          update_post_meta($product_id, 'mileage_km', sanitize_text_field($request->get_param('mileage')));
        }

        if ($request->get_param('horsepower') !== null) {
          update_post_meta($product_id, 'power_hp', sanitize_text_field($request->get_param('horsepower')));
        }

        if ($request->get_param('interiorColor') !== null) {
          update_post_meta($product_id, 'interior', sanitize_text_field($request->get_param('interiorColor')));
          update_post_meta($product_id, 'interior_color', sanitize_text_field($request->get_param('interiorColor')));
        }

        if ($request->get_param('exteriorColor') !== null) {
          update_post_meta($product_id, 'exterior', sanitize_text_field($request->get_param('exteriorColor')));
          update_post_meta($product_id, 'exterior_color', sanitize_text_field($request->get_param('exteriorColor')));
        }

        if ($request->get_param('year') !== null) {
          update_post_meta($product_id, 'year', sanitize_text_field($request->get_param('year')));
        }

        if ($request->get_param('make') !== null) {
          update_post_meta($product_id, 'make', sanitize_text_field($request->get_param('make')));
        }

        if ($request->get_param('model') !== null) {
          update_post_meta($product_id, 'model', sanitize_text_field($request->get_param('model')));
        }

        if ($request->get_param('transmissionType') !== null) {
          update_post_meta($product_id, 'transmission', sanitize_text_field($request->get_param('transmissionType')));
          update_post_meta($product_id, 'gearbox', sanitize_text_field($request->get_param('transmissionType')));
        }
      },
      'schema' => null,
    )
  );

  register_rest_field(
    'product',
    'images',
    array(
      'get_callback' => function ($object) {
        $product = wc_get_product($object['id']);
        $gallery_image_ids = $product->get_gallery_image_ids();
        $gallery_image_urls = array();
        $featured_image_id = get_post_thumbnail_id($object['id']);
        if ($featured_image_id) {
          $featured_image_url = wp_get_attachment_image_url($featured_image_id, 'full');
          $gallery_image_urls = array($featured_image_url);
        }
        foreach ($gallery_image_ids as $image_id) {
          $gallery_image_urls[] = wp_get_attachment_url($image_id);
        }
        return $gallery_image_urls;
      },
      'update_callback' => null,
      'schema' => null,
    )
  );

  register_rest_field(
    'product',
    'images_ids',
    array(
      'get_callback' => function ($object) {
        $product = wc_get_product($object['id']);
        $gallery_image_ids = $product->get_gallery_image_ids();
        $gallery_image_urls = array();
        $featured_image_id = get_post_thumbnail_id($object['id']);
        if ($featured_image_id) {
          $featured_image_url = wp_get_attachment_image_url($featured_image_id, 'full');
          $gallery_image_urls[$featured_image_url] = $featured_image_id;
        }
        foreach ($gallery_image_ids as $image_id) {
          $gallery_image_urls[wp_get_attachment_url($image_id)] = $image_id;
        }
        return $gallery_image_urls;
      },
      'update_callback' => null,
      'schema' => null,
    )
  );

  if (class_exists('acf')) {
    register_rest_field(
      'product',
      'video',
      array(
        'get_callback' => function ($object) {
          return get_field('video', $object['id']);
        },
        'update_callback' => null,
        'schema' => null,
      )
    );
  }

  register_rest_field(
    'product',
    'title_formatted',
    array(
      'get_callback' => function ($object) {
        $title = get_the_title($object['id']);
        $title_formatted = html_entity_decode($title);
        return $title_formatted;
      },
      'update_callback' => null,
      'schema' => null,
    )
  );

  register_rest_field(
    'product',
    'excerpt_unformatted',
    array(
      'get_callback' => function ($object) {
        $product = wc_get_product($object['id']);
        $excerpt = $product->get_short_description();
        // Strip all HTML tags
        $plain_excerpt = wp_strip_all_tags($excerpt);
        $plain_excerpt = preg_replace('/\s+/', ' ', $plain_excerpt);
        return $plain_excerpt;
      },
      'update_callback' => null,
      'schema' => null,
    )
  );

  if (function_exists('dokan_get_vendor_by_product')) {
    register_rest_field(
      'product',
      'vendor',
      array(
        'get_callback' => function ($object) {
          $vendor = dokan_get_vendor_by_product($object['id']);
          if (!$vendor) {
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
    'methods' => 'POST',
    'callback' => 'lux_create_product_with_image',
    'permission_callback' => '__return_true',
  ));

  register_rest_route('lux/v1', '/update-product', array(
    'methods' => 'POST',
    'callback' => 'lux_update_product_with_image',
    'permission_callback' => '__return_true',
  ));

  register_rest_route('lux/v1', '/report-product', array(
    'methods' => 'POST',
    'callback' => 'lux_report_product',
    'permission_callback' => '__return_true',
  ));


  // V2 Create Product Route
  register_rest_route('lux/v2', '/create-product', array(
    'methods' => 'POST',
    'callback' => 'lux_create_product_with_image_v2',
    'permission_callback' => '__return_true',
  ));

  // V2 Update Product Route  
  register_rest_route('lux/v2', '/update-product', array(
    'methods' => 'POST',
    'callback' => 'lux_update_product_with_image_v2',
    'permission_callback' => '__return_true',
  ));

  // V2 Report Product Route
  register_rest_route('lux/v2', '/report-product', array(
    'methods' => 'POST',
    'callback' => 'lux_report_product_v2',
    'permission_callback' => '__return_true',
  ));

});

add_filter('rest_prepare_product', function ($response, $post, $request) {
  if (isset($response->data['_links'])) {
    unset($response->data['_links']);
  }
  if (isset($response->data['featured_media'])) {
    unset($response->data['featured_media']);
  }
  if (isset($response->data['ping_status'])) {
    unset($response->data['ping_status']);
  }
  if (isset($response->data['meta'])) {
    unset($response->data['meta']);
  }
  if (isset($response->data['template'])) {
    unset($response->data['template']);
  }
  if (isset($response->data['class_list'])) {
    unset($response->data['class_list']);
  }
  return $response;
}, 10, 3);

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

function lux_create_product_with_image(WP_REST_Request $request)
{
  if (!class_exists('WC_Product')) {
    return new WP_Error('woocommerce_not_active', 'WooCommerce is not active', array('status' => 500));
  }

  // Get parameters
  $title = sanitize_text_field($request->get_param('title'));
  $price = sanitize_text_field($request->get_param('regular_price'));
  $category_id = (int) $request->get_param('category_id') ?: 1;
  $fileParams = $request->get_file_params();

  $image = $fileParams['thumbnail'];
  $gallery_images = isset($fileParams['gallery_images']) ? $fileParams['gallery_images'] : [];

  // var_dump($gallery_images);
  if (!$title || !$price || !$image) {
    return new WP_Error('missing_params', 'Missing required parameters: title, price, or image', array('status' => 400));
  }
  $product = new WC_Product_Simple();
  $product->set_name($title);
  $product->set_regular_price($price);
  $product->set_category_ids([$category_id]);
  $product->set_status('pending');
  // Handle main image upload
  $upload = wp_handle_upload($image, ['test_form' => false]);
  if (isset($upload['error'])) {
    return new WP_Error('image_upload_failed', $upload['error'], ['status' => 500]);
  }

  $attachment_id = lux_create_attachment($upload['file'], $upload['url']);
  if ($attachment_id) {
    $product->set_image_id($attachment_id);
  }

  // Handle gallery images
  $galleryImageIds = [];
  $galleryImageIdsError = [];

  if (!empty($gallery_images)) {
    $galleryImageIds = [];
    $galleryImageIdsError = [];

    // Iterate over the files
    foreach ($gallery_images['name'] as $index => $name) {
      // Create a single file array for wp_handle_upload
      $file = [
        'name' => $gallery_images['name'][$index],
        'type' => $gallery_images['type'][$index],
        'tmp_name' => $gallery_images['tmp_name'][$index],
        'error' => $gallery_images['error'][$index],
        'size' => $gallery_images['size'][$index],
      ];

      // Process each file
      $upload_gallery = wp_handle_upload($file, ['test_form' => false]);

      if (!isset($upload_gallery['error'])) {
        $gallery_attachment_id = lux_create_attachment($upload_gallery['file'], $upload_gallery['url']);
        if ($gallery_attachment_id) {
          $galleryImageIds[] = $gallery_attachment_id;
        }
      } else {
        $galleryImageIdsError[] = $upload_gallery['error'];
        error_log($upload_gallery['error']);
      }
    }
  }
  $product->set_gallery_image_ids($galleryImageIds);

  // Create WooCommerce product

  $product_id = $product->save();
  if (!$product_id) {
    return new WP_Error('product_creation_failed', 'Failed to create product', ['status' => 500]);
  }

  if ($request->get_param('mileage') !== null) {
    update_post_meta($product_id, 'mileage', sanitize_text_field($request->get_param('mileage')));
    update_post_meta($product_id, 'mileage_km', sanitize_text_field($request->get_param('mileage')));
  }

  if ($request->get_param('horsepower') !== null) {
    update_post_meta($product_id, 'power_hp', sanitize_text_field($request->get_param('horsepower')));
  }

  if ($request->get_param('interiorColor') !== null) {
    update_post_meta($product_id, 'interior', sanitize_text_field($request->get_param('interiorColor')));
    update_post_meta($product_id, 'interior_color', sanitize_text_field($request->get_param('interiorColor')));
  }

  if ($request->get_param('exteriorColor') !== null) {
    update_post_meta($product_id, 'exterior', sanitize_text_field($request->get_param('exteriorColor')));
    update_post_meta($product_id, 'exterior_color', sanitize_text_field($request->get_param('exteriorColor')));
  }

  if ($request->get_param('year') !== null) {
    update_post_meta($product_id, 'year', sanitize_text_field($request->get_param('year')));
  }

  if ($request->get_param('make') !== null) {
    update_post_meta($product_id, 'make', sanitize_text_field($request->get_param('make')));
  }

  if ($request->get_param('model') !== null) {
    update_post_meta($product_id, 'model', sanitize_text_field($request->get_param('model')));
  }

  if ($request->get_param('transmissionType') !== null) {
    update_post_meta($product_id, 'transmission', sanitize_text_field($request->get_param('transmissionType')));
    update_post_meta($product_id, 'gearbox', sanitize_text_field($request->get_param('transmissionType')));
  }

  // Update post author
  wp_update_post([
    'ID' => $product_id,
    'post_author' => get_current_user_id(),
  ]);

  // Return product info
  return new WP_REST_Response([
    'id' => $product_id,
    'title' => $title,
    'price' => $price,
    'image_id' => $attachment_id,
    'gallery_image_ids' => $galleryImageIds,
    'gallery_errors' => json_encode($galleryImageIdsError),
  ], 200);
}
function lux_create_product_with_image_v2(WP_REST_Request $request)
{
  // Check if Vires API function exists
  if (!function_exists('vires_validate_api_key')) {
    return new WP_Error(
      'vires_not_available',
      'Vires API Keys plugin not active',
      array('status' => 500)
    );
  }

  // Validate API key (header only)
  $api_validation = vires_validate_api_key($request, ['auth_method' => 'header']);

  if (is_wp_error($api_validation)) {
    return $api_validation;
  }


  return lux_create_product_with_image($request);
}

function lux_update_product_with_image(WP_REST_Request $request)
{
  // Ensure WooCommerce is active
  if (!class_exists('WC_Product')) {
    return new WP_Error('woocommerce_not_active', 'WooCommerce is not active', array('status' => 500));
  }
  // Get parameters
  $product_id = sanitize_text_field($request->get_param('id'));
  $title = sanitize_text_field($request->get_param('title'));
  $price = sanitize_text_field($request->get_param('regular_price'));
  $category_id = (int) (sanitize_text_field($request->get_param('category_id')) ?: 1);

  // $image = $request->get_file_params()['thumbnail'];

  // if (!$title || !$price || !$image) {
  //   return new WP_Error('missing_params', 'Missing required parameters: title, price, or image', array('status' => 400));
  // }
  if (!$product_id || !$title || !$price) {
    return new WP_Error('missing_params', 'Missing required parameters: title, price, or image', array('status' => 400));
  }
  $product = new WC_Product_Simple($product_id);
  $product->set_name($title);
  $product->set_regular_price($price);
  $product->set_category_ids([$category_id]);

  $fileParams = $request->get_file_params();

  if ($image = $fileParams['thumbnail']) {

    // Handle main image upload
    $upload = wp_handle_upload($image, ['test_form' => false]);
    if (isset($upload['error'])) {
      return new WP_Error('image_upload_failed', $upload['error'], ['status' => 500]);
    }

    $attachment_id = lux_create_attachment($upload['file'], $upload['url'], $product_id);
    if (!$attachment_id) {
      return new WP_Error('attachment_creation_failed', 'Failed to create attachment', ['status' => 500]);
    } else {
      $product->set_image_id($attachment_id);

    }
  }


  // Handle gallery images
  $galleryImageIds = $product->get_gallery_image_ids();

  foreach ($request->get_param('deleted_gallery_image_ids') as $deleted_gallery_image_id) {
    if (($key = array_search($deleted_gallery_image_id, $galleryImageIds)) !== false) {
      wp_delete_attachment($deleted_gallery_image_id);
      unset($galleryImageIds[$key]);
    }
  }

  $galleryImageIdsError = [];

  $gallery_images = isset($fileParams['gallery_images']) ? $fileParams['gallery_images'] : [];
  if (!empty($gallery_images)) {
    // Iterate over the files
    foreach ($gallery_images['name'] as $index => $name) {
      // Create a single file array for wp_handle_upload
      $file = [
        'name' => $gallery_images['name'][$index],
        'type' => $gallery_images['type'][$index],
        'tmp_name' => $gallery_images['tmp_name'][$index],
        'error' => $gallery_images['error'][$index],
        'size' => $gallery_images['size'][$index],
      ];

      // Process each file
      $upload_gallery = wp_handle_upload($file, ['test_form' => false]);

      if (!isset($upload_gallery['error'])) {
        $gallery_attachment_id = lux_create_attachment($upload_gallery['file'], $upload_gallery['url'], $product_id);
        if ($gallery_attachment_id) {
          $galleryImageIds[] = $gallery_attachment_id;
        }
      } else {
        $galleryImageIdsError[] = $upload_gallery['error'];
      }
    }
  }

  // Create WooCommerce product
  $product->set_gallery_image_ids($galleryImageIds);

  $product_id = $product->save();
  if (!$product_id) {
    return new WP_Error('product_creation_failed', 'Failed to create product', array('status' => 500));
  }

  if ($request->get_param('mileage') !== null) {
    update_post_meta($product_id, 'mileage', sanitize_text_field($request->get_param('mileage')));
    update_post_meta($product_id, 'mileage_km', sanitize_text_field($request->get_param('mileage')));
  }

  if ($request->get_param('horsepower') !== null) {
    update_post_meta($product_id, 'power_hp', sanitize_text_field($request->get_param('horsepower')));
  }

  if ($request->get_param('interiorColor') !== null) {
    update_post_meta($product_id, 'interior', sanitize_text_field($request->get_param('interiorColor')));
    update_post_meta($product_id, 'interior_color', sanitize_text_field($request->get_param('interiorColor')));
  }

  if ($request->get_param('exteriorColor') !== null) {
    update_post_meta($product_id, 'exterior', sanitize_text_field($request->get_param('exteriorColor')));
    update_post_meta($product_id, 'exterior_color', sanitize_text_field($request->get_param('exteriorColor')));
  }

  if ($request->get_param('year') !== null) {
    update_post_meta($product_id, 'year', sanitize_text_field($request->get_param('year')));
  }

  if ($request->get_param('make') !== null) {
    update_post_meta($product_id, 'make', sanitize_text_field($request->get_param('make')));
  }

  if ($request->get_param('model') !== null) {
    update_post_meta($product_id, 'model', sanitize_text_field($request->get_param('model')));
  }

  if ($request->get_param('transmissionType') !== null) {
    update_post_meta($product_id, 'transmission', sanitize_text_field($request->get_param('transmissionType')));
    update_post_meta($product_id, 'gearbox', sanitize_text_field($request->get_param('transmissionType')));
  }

  // Return product info
  return new WP_REST_Response([
    'id' => $product_id,
    'title' => $title,
    'price' => $price,
    // 'image_id' => $attachment_id,
  ], 200);
}

function lux_update_product_with_image_v2(WP_REST_Request $request)
{

  if (!function_exists('vires_validate_api_key')) {
    return new WP_Error(
      'vires_not_available',
      'Vires API Keys plugin not active',
      array('status' => 500)
    );
  }


  $api_validation = vires_validate_api_key($request, ['auth_method' => 'header']);

  if (is_wp_error($api_validation)) {
    return $api_validation;
  }


  return lux_update_product_with_image($request);
}


add_filter('woocommerce_rest_check_permissions', function ($permission, $context, $object_id, $post_type) {
  if ($post_type === 'product' && current_user_can('customer')) {
    if ($context === 'edit' || $context === 'delete') {
      if (get_post($object_id)->post_author == get_current_user_id()) {
        return true;
      }
    } else if ($context == 'read' || $context === 'create') {
      return true;
    }
  }
  return $permission;
}, 10, 4);

function lux_report_product(WP_REST_Request $request)
{
  if (!class_exists('WC_Product')) {
    return new WP_Error('woocommerce_not_active', 'WooCommerce is not active', array('status' => 500));
  }
  $product_id = (int) $request->get_param('product_id');
  $report_reason = $request->get_param('report_reason');

  // var_dump($gallery_images);
  if (!$product_id || !$report_reason) {
    return new WP_Error('missing_params', 'Missing required parameters: Product id, Report Issue', array('status' => 400));
  }
  $admin_email = get_option('admin_email');

  $subject = 'User Reported A Product';

  $message = 'Product ID: ' . $product_id . ' <br>';
  $message .= 'User ID: ' . get_current_user_id() . ' <br>';
  $message .= 'Reason: ' . $report_reason . ' <br>';

  $headers = array('Content-Type: text/html; charset=UTF-8');
  wp_mail($admin_email, $subject, $message, $headers);
}

function lux_report_product_v2(WP_REST_Request $request)
{

  if (!function_exists('vires_validate_api_key')) {
    return new WP_Error(
      'vires_not_available',
      'Vires API Keys plugin not active',
      array('status' => 500)
    );
  }

  // Validate API key (header only)
  $api_validation = vires_validate_api_key($request, ['auth_method' => 'header']);

  if (is_wp_error($api_validation)) {
    return $api_validation;
  }


  return lux_report_product($request);
}