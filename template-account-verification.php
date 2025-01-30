<?php
/**
 * Template Name: Account Verification
 */

get_header();

function lux_verify_email_verification_code($code, $user_id = null) {
  // if (!$user_id) {
  //   if (!is_user_logged_in()) {
  //     return false;
  //   }
  //   $user_id = get_current_user_id();
  // }
  $code = serialize($code); // Notice: unserialize()
  $code = base64_encode($code);

  $users = get_users([
    'meta_key' => 'email_verification_code',
    'meta_value' => $code
  ]);
  if ($users && count($users) && $users[0]) {
    update_user_meta($users[0]->ID, 'email_verified', true);
    return true;
  }
  return false;
}
?>
<main id="main" class="site-main">
  <div class="container">
    <section id="verifcation-status" class="py-lg-5">
      <div class="row justify-content-center">
        <div class="col-auto text-center">
          <?php
          $user_id = get_current_user_id();
          $verification_code = isset($_GET['code']) && $_GET['code'] ? $_GET['code'] : false;
          if ($verification_code) { ?>
            <?php if (lux_verify_email_verification_code($verification_code, $user_id)) { ?>
              <div class="fa-stack fa-lg mb-3">
                <i class="fa fa-certificate fa-stack-2x" style="color: #1DA1F2;"></i>
                <i class="fa fa-check fa-stack-1x fa-inverse text-white"></i>
              </div>
              <div class="alert alert-success">Congratulations, your LUX account is email verified.</div>
            <?php } else { ?>
              <div class="alert alert-danger">Failed to confirm your email verification code.</div>
              <a href="javascript:void(0)" class="btn btn-outline-white btn-verify-account">
                Verify Account
              </a>
            <?php } ?>
          <?php } else {
            $is_verified = get_user_meta($user_id, 'email_verified', true);
            if (!$is_verified) { ?>
              <a href="javascript:void(0)" class="btn btn-outline-white btn-verify-account">
                Verify Account
              </a>
            <?php } else { ?>
              <div class="fa-stack fa-lg mb-3">
                <i class="fa fa-certificate fa-stack-2x" style="color: #1DA1F2;"></i>
                <i class="fa fa-check fa-stack-1x fa-inverse text-white"></i>
              </div>
              <div class="alert alert-success">Congratulations, your LUX account is email verified.</div>
            <?php } ?>
          <?php } ?>
        </div>
      </div> 
    </section>
  </div>
</main>
<?php get_footer(); ?>
