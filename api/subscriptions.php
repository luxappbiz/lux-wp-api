<?php

/**
 * Register a custom REST API endpoint to retrieve WooCommerce Subscriptions by user ID.
 *
 * @package LUX_Subscriptions_API
 */

/**
 * Register the REST API endpoint when REST API is initialized.
 */
add_action('rest_api_init', function () {
  register_rest_route('lux/v1', '/subscriptions', array(
    // 'methods'  => WP_REST_Server::READABLE, // GET request
    'methods' => 'GET',
    'callback' => 'lux_get_all_subscriptions',
    // 'permission_callback' => 'lux_all_subscriptions_permissions_check',
    'permission_callback' => '__return_true',
    // 'permission_callback' => function($request) {
    //   return is_user_logged_in();
    // },
  ));
  register_rest_route('lux/v1', '/users/(?P<user_id>\d+)/subscriptions', array(
	  // 'methods'  => WP_REST_Server::READABLE, // GET request
	  'methods' => 'GET',
	  'callback' => 'lux_get_user_subscriptions',
	  // 'permission_callback' => 'lux_subscriptions_permissions_check',
    // 'permission_callback' => '__return_true',
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
	  'args'     => array(
      'user_id' => array(
        'validate_callback' => function ($param, $request, $key) {
          return is_numeric($param) && $param > 0;
        },
        'required' => true,
      ),
	  ),
	));
  register_rest_route('lux/v1', '/users/(?P<user_id>\d+)/membership', array(
	  'methods'  => WP_REST_Server::READABLE, // GET request
	  'callback' => 'lux_get_user_membership',
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
	  'args'     => array(
      'user_id' => array(
        'validate_callback' => function ($param, $request, $key) {
          return is_numeric($param) && $param > 0;
        },
        'required' => true,
      ),
	  ),
	));
	// Endpoint: /wp-json/lux/v1/subscriptions/{subscription_id}
	register_rest_route('lux/v1', '/subscriptions/(?P<subscription_id>\d+)', array(
	  'methods'  => WP_REST_Server::READABLE, // GET request
	  'callback' => 'lux_get_single_subscription',
	  // 'permission_callback' => 'lux_single_subscription_permissions_check',
    // 'permission_callback' => '__return_true',
    'permission_callback' => function($request) {
      return is_user_logged_in();
    },
	  'args'     => array(
			'subscription_id' => array(
			  'validate_callback' => function ($param, $request, $key) {
		      return is_numeric($param) && $param > 0;
			  },
			  'required' => true,
			),
	  ),
	));
});

function lux_get_all_subscriptions(WP_REST_Request $request) {
  if (!class_exists('WC_Subscriptions') || !function_exists('wcs_get_users_subscriptions')) {
    return new WP_Error(
      'subscriptions_not_available',
      __('WooCommerce Subscriptions is not active.', 'lux-subscriptions-api'),
      array('status' => 400)
    );
  }
  $subscriptions = wcs_get_subscriptions( array(
    'subscription_status' => 'any', // 'active', 'pending', 'cancelled', etc.
    'limit'               => -1     // Get all
  ) );
  $all_subscriptions = array();
  foreach ( $subscriptions as $subscription_id => $subscription ) {
    $new_subscription = array();
    $new_subscription['id'] = $subscription_id;
    $new_subscription['status'] = $subscription->get_status();
    $new_subscription['billing_period'] = $subscription->get_billing_period();
    $new_subscription['billing_interval'] = $subscription->get_billing_interval();
    $new_subscription['customer_id'] = $subscription->get_customer_id();
    $new_subscription['billing_email'] = $subscription->get_billing_email();
    $new_subscription['start_date'] = $subscription->get_time( 'start' );
    $new_subscription['next_payment'] = $subscription->get_date('next_payment');
    $new_subscription['end_date'] = $subscription->get_date('end');
    $new_subscription['total'] = wc_format_decimal($subscription->get_total(), 2);
    $new_subscription['currency'] = $subscription->get_currency();
    $new_subscription['items'] = array_map(function ($item) {
      $product = $item->get_product();
      return array(
        'product_id' => $item->get_product_id(),
        'name'       => $item->get_name(),
        'quantity'   => $item->get_quantity(),
        'subtotal'   => wc_format_decimal($item->get_subtotal(), 2),
      );
    }, $subscription->get_items());
    $all_subscriptions[] = $new_subscription;
  }
  return new WP_REST_Response(array(
    'all_subscriptions' => $all_subscriptions,
  ), 200);
}

/**
 * Callback function to handle the REST API request for user subscriptions.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return WP_REST_Response|WP_Error The response or an error.
 */
function lux_get_user_subscriptions(WP_REST_Request $request) {
  // Check if WooCommerce and WooCommerce Subscriptions are active
  if (!class_exists('WC_Subscriptions') || !function_exists('wcs_get_users_subscriptions')) {
    return new WP_Error(
      'subscriptions_not_available',
      __('WooCommerce Subscriptions is not active.', 'lux-subscriptions-api'),
      array('status' => 400)
    );
  }
  // Get the user ID from the request
  $user_id = (int) $request['user_id'];
  // Verify the user exists
  $user = get_user_by('id', $user_id);
  if (!$user) {
    return new WP_Error(
      'user_not_found',
      __('User not found.', 'lux-subscriptions-api'),
      array('status' => 404)
    );
  }
	// Get subscriptions for the user
	$subscriptions = wcs_get_users_subscriptions($user_id);
	if (empty($subscriptions)) {
	  return new WP_REST_Response(array(
      'message' => __('No subscriptions found for this user.', 'lux-subscriptions-api'),
      'subscriptions' => array(),
	  ), 200);
	}
  $subscription_data = array();
  // Loop through subscriptions and collect relevant data
  foreach ($subscriptions as $subscription) {
    $subscription_data[] = array(
      'id'               => $subscription->get_id(),
      'status'           => $subscription->get_status(),
      'billing_period'   => $subscription->get_billing_period(),
      'billing_interval' => $subscription->get_billing_interval(),
      'start_date'       => $subscription->get_date('start', 'lux'),
      'next_payment'     => $subscription->get_date('next_payment', 'lux'),
      'end_date'         => $subscription->get_date('end', 'lux'),
      'total'            => wc_format_decimal($subscription->get_total(), 2),
      'currency'         => $subscription->get_currency(),
      'items'            => array_map(function ($item) {
        $product = $item->get_product();
        return array(
          'product_id' => $item->get_product_id(),
          'name'       => $item->get_name(),
          'quantity'   => $item->get_quantity(),
          'subtotal'   => wc_format_decimal($item->get_subtotal(), 2),
        );
      }, $subscription->get_items()),
    );
  }
  return new WP_REST_Response(array(
    'subscriptions' => $subscription_data,
  ), 200);
}

function lux_get_user_membership(WP_REST_Request $request) {
  if (!class_exists('WC_Subscriptions') || !function_exists('wcs_get_users_subscriptions')) {
    return new WP_Error(
      'subscriptions_not_available',
      __('WooCommerce Subscriptions is not active.', 'lux-subscriptions-api'),
      array('status' => 400)
    );
  }
  $user_id = (int) $request['user_id'];
  $user = get_user_by('id', $user_id);
  if (!$user) {
    return new WP_Error(
      'user_not_found',
      __('User not found.', 'lux-subscriptions-api'),
      array('status' => 404)
    );
  }
  $subscriptions = wcs_get_users_subscriptions($user_id);
  foreach ($subscriptions as $subscription) {
    foreach ($subscription->get_items() as $item) {
      if ((int) $item->get_product_id() === 482) {
        return new WP_REST_Response(array(
          'membership' => array(
            'id'               => $subscription->get_id(),
            'status'           => $subscription->get_status(),
            'billing_period'   => $subscription->get_billing_period(),
            'billing_interval' => $subscription->get_billing_interval(),
            'start_date'       => $subscription->get_date('start', 'site'),
            'next_payment'     => $subscription->get_date('next_payment', 'site'),
            'end_date'         => $subscription->get_date('end', 'site'),
            'total'            => wc_format_decimal($subscription->get_total(), 2),
            'currency'         => $subscription->get_currency(),
            'product_id'       => $item->get_product_id(),
            'product_name'     => $item->get_name(),
            'quantity'         => $item->get_quantity(),
            'subtotal'         => wc_format_decimal($item->get_subtotal(), 2),
          ),
        ), 200);
      }
    }
  }
  return new WP_REST_Response(array(
    'membership' => null,
    'message' => __('No membership subscription found for this user.', 'lux-subscriptions-api'),
  ), 200);
}

function lux_subscriptions_permissions_check(WP_REST_Request $request) {
  $user_id = (int) $request['user_id'];
  $current_user_id = get_current_user_id();
  // Allow access if the user is requesting their own subscriptions or is an admin
  if ($current_user_id === $user_id || current_user_can('manage_woocommerce')) {
    return true;
  }
  return new WP_Error(
    'rest_forbidden',
    __('You do not have permission to access this user’s subscriptions.', 'lux-subscriptions-api'),
    array('status' => 403)
  );
}

function lux_get_single_subscription(WP_REST_Request $request) {
  // Check if WooCommerce and WooCommerce Subscriptions are active
  if (!class_exists('WC_Subscriptions') || !function_exists('wcs_get_users_subscriptions')) {
    return new WP_Error(
      'subscriptions_not_available',
      __('WooCommerce Subscriptions is not active.', 'lux-subscriptions-api'),
      array('status' => 400)
    );
  }
  // Get the user ID from the request
  $subscription_id = (int) $request['subscription_id'];
  $subscription = wcs_get_subscription($subscription_id);
  // Extract relevant subscription data
  $subscription_data = [
    'id' => $subscription->get_id(),
    'status' => $subscription->get_status(),
    'billing_period' => $subscription->get_billing_period(),
    'billing_interval' => $subscription->get_billing_interval(),
    'start_date' => $subscription->get_date('start', 'site'),
    'next_payment_date' => $subscription->get_date('next_payment', 'site'),
    'end_date' => $subscription->get_date('end', 'site'),
    'total' => $subscription->get_total(),
    'currency' => $subscription->get_currency(),
    'customer_id' => $subscription->get_customer_id(),
    'customer_email' => $subscription->get_billing_email(),
    'order_id' => $subscription->get_parent_id(),
    'products' => [],
  ];
  // Get products in the subscription
  foreach ($subscription->get_items() as $item) {
    $product = $item->get_product();
    $subscription_data['products'][] = [
      'product_id' => $item->get_product_id(),
      'name' => $item->get_name(),
      'quantity' => $item->get_quantity(),
      'subtotal' => $item->get_subtotal(),
    ];
  }
  return new WP_REST_Response(array(
    // 'subscription_id' => $subscription_id,
    'data' => $subscription_data,
  ), 200);
}

/**
 * Permission callback for the single subscription endpoint.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return bool|WP_Error True if permitted, WP_Error if not.
 */
function lux_single_subscription_permissions_check(WP_REST_Request $request) {
  $subscription_id = (int) $request['subscription_id'];
  $current_user_id = get_current_user_id();
  // Retrieve the subscription to check ownership
  $subscription = wcs_get_subscription($subscription_id);
  if (!$subscription) {
	  return new WP_Error(
	    'subscription_not_found',
	    __('Subscription not found.', 'lux-subscriptions-api'),
	    array('status' => 404)
	  );
  }
  $subscription_user_id = $subscription->get_user_id();
  // Allow access if the user owns the subscription or is an admin
  // if ($current_user_id === $subscription_user_id || current_user_can('manage_woocommerce')) {
    return true;
  // }
  // return new WP_Error(
  //   'rest_forbidden',
  //   __('You do not have permission to access this subscription.', 'lux-subscriptions-api'),
  //   array('status' => 403)
  // );
}
