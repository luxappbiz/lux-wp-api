
<style>
.woocommerce .col2-set .col-1,
.woocommerce-page .col2-set .col-1 {
  float: left;
  width: 48%;
  max-width: initial;
}
.woocommerce .col2-set .col-2,
.woocommerce-page .col2-set .col-2 {
  float: right;
  width: 48%;
  max-width: initial;
}
.woocommerce form .form-row .password-input {
  width: 100%;
}
.woocommerce form .form-row {
  display: flex;
  flex-direction: column;
}
.woocommerce-form .woocommerce-button {
  display: block;
  width: 100%;
  margin: 0;
  background-color: black !important;
  color: white !important;
  font-family: "Playfair Display", Sans-serif;
}
</style>
<section class="container py-3 py-lg-5">
  <?php echo do_shortcode('[woocommerce_my_account]'); ?>      
</section>
