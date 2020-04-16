<?php
//* Code goes here

/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
function hello_elementor_child_enqueue_scripts() {
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		'1.0.0'
	);

	wp_enqueue_style(
		'semantic-ui',
		get_stylesheet_directory_uri() . '/semantic/semantic.min.css',
		[],
		'1.0.0'
	);

	wp_enqueue_script(
		'semantic-ui-js',
		get_stylesheet_directory_uri() . '/semantic/semantic.min.css', 
		['jquery'], 
		'2.0.0', 
		true 
	);
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts' );