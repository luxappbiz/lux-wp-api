<?php
/**
 * Template Name: API Documentation
 *
 * @package Martfury
 */

get_header(); ?>
<?php if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'single' ) ) { ?>
  <div id="primary" class="content-area <?php martfury_content_columns(); ?>">
    <main id="main" class="site-main">

      <?php while ( have_posts() ) : the_post(); ?>

<section data-spy="scroll" data-target="#myScrollspy" data-offset="150">
  <div class="container">
    <div class="row">
      <div class="col-xs-3" id="myScrollspy">
        <ul class="nav nav-pills nav-stacked" id="navbarExample">
          <li><a href="#section1">Section 1</a></li>
          <li><a href="#section2">Section 2</a></li>
          <li><a href="#section3">Section 3</a></li>
        </ul>
      </div>
      <div class="col-xs-9">
        <h1>Main Content</h1>
        <div id="section1">
          <h2>Section 1</h2>
          <p>Content for section 1...</p>
        </div>
        <div id="section2">
          <h2>Section 2</h2>
          <p>Content for section 2...</p>
        </div>
        <div id="section3">
          <h2>Section 3</h2>
          <p>Content for section 3...</p>
        </div>
      </div>
    </div>
  </div>
  <script>
  $(document).ready(function() {
    $('#myScrollspy').affix({
      offset: { 
        top: 125 
      }
    });
    // Scrollspy activation
    $('body').scrollspy({ target: "#myScrollspy" });
    // Smooth scrolling for navigation
    $("#navbarExample a").on('click', function(event) {
      if (this.hash !== "") {
        event.preventDefault();
        var hash = this.hash;
        $('html, body').animate({
            scrollTop: $(hash).offset().top
        }, 800, function(){
            window.location.hash = hash;
        });
      }
    });
  });
  </script>
</section>

      <?php endwhile; // end of the loop. ?>

    </main><!-- #main -->
  </div><!-- #primary -->
    <?php get_sidebar(); ?>
<?php } ?>
<?php get_footer(); ?>
