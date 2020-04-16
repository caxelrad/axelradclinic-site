<?php
/**
 * Template Name: Empty Page Template
 *
 * Template for displaying a page just with the header and footer area and a "naked" content area in between.
 * Good for landingpages and other types of pages where you want to add a lot of custom markup.
 *
 * @package understrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();
?>

<div class="wrapper" id="content">
    <?php 
  while ( have_posts() ) : the_post();

  get_template_part( 'loop-templates/content', 'blank' );

  endwhile; // end of the loop.
  
  wp_footer(); 
  ?>
</div>