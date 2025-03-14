
<?php
$is_user_logged_in = is_user_logged_in();
$current_user_id = $is_user_logged_in ? get_current_user_id() : false;
$membership_subscription_id = $current_user_id ? lux_get_membership_subscription_id($current_user_id) : false;
if ($membership_subscription_id) {
	$membership_subscription_id = ($membership_subscription_id['success']) ? $membership_subscription_id['id'] : false;	
}
if (!$membership_subscription_id) {
	get_template_part('partials/modal-membership-unlock');
} ?>

<?php wp_footer(); ?>

</body>
</html>
