<?php

/**
 * Dynamically generate CSS code.
 */

function bloglamp_dynamic_styles($css) {
	// Category color.
	$categories = get_categories( array( 'hide_empty' => 1 ) );
	foreach ( $categories as $category ) {
		$term_id   = $category->term_id;
		$cat_color = bloghash_option( 'category_color_' . strtolower( $term_id ) );

		$css .= '
		.post-category .cat-links a.cat-' . absint( $term_id ) . ' {
			color: #fff;
			background: ' . bloghash_sanitize_color( $cat_color ) . '
		}
		.post-category .cat-links a.cat-' . absint( $term_id ) . ':focus,
		.post-category .cat-links a.cat-' . absint( $term_id ) . ':hover {
			color: #fff;
			background: ' . bloghash_sanitize_color( bloghash_luminance( $cat_color, .15 ) ) . '
		} ';
	}

	return $css;
}

add_filter( 'bloghash_dynamic_styles', 'bloglamp_dynamic_styles' );