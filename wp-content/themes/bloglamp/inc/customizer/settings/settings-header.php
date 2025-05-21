<?php

add_filter( 'bloghash_customizer_options', 'bloglamp_customizer_header_options', 11 );
function bloglamp_customizer_header_options( array $options ) {
	// Header Layout.
	$options['setting']['bloghash_header_layout']['control']['choices'] = array(
		'layout-2' => array(
			'image' => get_stylesheet_directory_uri() . '/inc/customizer/assets/images/header-layout-2.svg',
			'title' => esc_html__( 'Header 1', 'bloglamp' ),
		),
	);

	return $options;
}
