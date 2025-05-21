<?php

add_filter( 'bloghash_customizer_options', 'bloglamp_customizer_blog_options', 11 );
function bloglamp_customizer_blog_options( array $options ) {
	// Layout.
	$options['setting']['bloghash_blog_layout'] = array(
		'transport'         => 'refresh',
		'sanitize_callback' => 'bloghash_sanitize_select',
		'control'           => array(
			'type'        => 'bloghash-select',
			'label'       => esc_html__( 'Layout', 'bloglamp' ),
			'section'     => 'bloghash_section_blog_page',
			'choices'     => array(
				'blog-horizontal' => esc_html__( 'Horizontal', 'bloglamp' ),
			),
		),
	);

	return $options;
}
