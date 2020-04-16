<?php
/**
 * Template Name: Blank with Fluid Container
 *
 * Template for displaying a blank page.
 *
 * @package understrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body>
  <div class="container-fluid">
    <div class="row">
      <?php while ( have_posts() ) : the_post(); ?>

        <?php get_template_part( 'loop-templates/content', 'blank' ); ?>

      <?php endwhile; // end of the loop. ?>
    </div>
    <div class="row">
      <?php wp_footer(); ?>
    </div>
  </div>
</body>
</html>
