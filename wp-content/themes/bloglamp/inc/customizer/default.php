<?php

/**
 * Default Option
 */

add_filter( 'bloghash_default_option_values', 'bloghash_default_options', 11 );
function bloghash_default_options( $defaults ) {

	$defaults['bloghash_accent_color']     					= '#525CEB';
	$defaults['bloghash_text_color']       					= '#0a0a0a';
	$defaults['bloghash_link_hover_color'] 					= '#525CEB';
	$defaults['bloghash_headings_color']   					= '#0a0a0a';

	$defaults['bloghash_enable_cursor_dot'] 				= false;
	
	$defaults['bloghash_body_animation']    				= true;

	$defaults['bloghash_site_layout'] 						= 'fw-contained';

	$defaults['bloghash_body_font'] 						= bloghash_typography_defaults(
		array(
			'font-family'         => 'Be Vietnam Pro',
			'font-weight'         => 400,
			'font-size-desktop'   => '1.7',
			'font-size-unit'      => 'rem',
			'line-height-desktop' => '1.75',
		)
	);

	$defaults['bloghash_logo_margin'] 						= array(
		'desktop' => array(
			'top'    => 25,
			'right'  => 3,
			'bottom' => 25,
			'left'   => 0,
		),
		'tablet'  => array(
			'top'    => 25,
			'right'  => 1,
			'bottom' => 25,
			'left'   => 0,
		),
		'mobile'  => array(
			'top'    => '',
			'right'  => '',
			'bottom' => '',
			'left'   => '',
		),
		'unit'    => 'px',
	);

	$defaults['bloghash_primary_button_bg_color']           = '';
	$defaults['bloghash_primary_button_hover_bg_color']     = '';
	$defaults['bloghash_primary_button_text_color']         = '#fff';
	$defaults['bloghash_primary_button_hover_text_color']   = '#fff';
	$defaults['bloghash_primary_button_border_radius']      = array(
		'top-left'     => '',
		'top-right'    => '',
		'bottom-right' => '',
		'bottom-left'  => '',
		'unit'         => 'rem',
	);
	$defaults['bloghash_primary_button_border_width']       = 0.1;
	$defaults['bloghash_primary_button_border_color']       = '';
	$defaults['bloghash_primary_button_hover_border_color'] = '';

	$defaults['bloghash_secondary_button_border_radius'] 	= array(
		'top-left'     => '',
		'top-right'    => '',
		'bottom-right' => '',
		'bottom-left'  => '',
		'unit'         => 'rem',
	);
	$defaults['bloghash_secondary_button_border_width']  	= 0.1;

	$defaults['bloghash_text_button_text_color']          	= '#0a0a0a';
	$defaults['bloghash_text_button_hover_text_color'] 		= '';

	$defaults['bloghash_section_heading_style'] 			= '3';

	$defaults['bloghash_blog_zig_zag'] 						= true;
	$defaults['bloghash_blog_card_border'] 					= true;
	$defaults['bloghash_blog_card_shadow'] 					= false;

	$defaults['bloghash_blog_layout'] 						= 'blog-horizontal';
	$defaults['bloghash_blog_image_wrap'] 					= false;

	$defaults['bloghash_sidebar_widget_title_font_size'] 	= array(
		'desktop' => 2,
		'unit'    => 'rem',
	);

	$defaults['bloghash_top_bar_enable'] 					= false;

	$defaults['bloghash_header_background'] 				= bloghash_design_options_defaults(
		array(
			'background' => array(
				'color'    => array(
					'background-color' => '#ffffff',
				),
				'gradient' => array(),
			),
		)
	);

	$defaults['bloghash_header_border'] 					= bloghash_design_options_defaults(
		array(
			'border' => array(
				'border-bottom-width' => '',
				'border-color'        => '',
				'separator-color'     => '#cccccc',
			),
		)
	);

	$defaults['bloghash_header_layout'] 					= 'layout-2';
	$defaults['bloghash_header_widgets'] 					= array(
		array(
			'classname' => 'bloghash_customizer_widget_socials',
			'type'      => 'socials',
			'values'    => array(
				'style'      => 'minimal-fill',
				'size'       => 'large',
				'location'   => 'right',
				'visibility' => 'hide-mobile-tablet',
			),
		),
		array(
			'classname' => 'bloghash_customizer_widget_darkmode',
			'type'      => 'darkmode',
			'values'    => array(
				'style'      => 'rounded-border',
				'location'   => 'right',
				'visibility' => 'hide-mobile-tablet',
			),
		),
		array(
			'classname' => 'bloghash_customizer_widget_search',
			'type'      => 'search',
			'values'    => array(
				'style'      => 'rounded-fill',
				'location'   => 'right',
				'visibility' => 'hide-mobile-tablet',
			),
		),
		array(
			'classname' => 'bloghash_customizer_widget_button',
			'type'      => 'button',
			'values'    => array(
				'text'       => '<i class="far fa-bell mr-1 bloghash-icon"></i> Subscribe',
				'url'        => '#',
				'class'      => 'btn-small',
				'target'     => true,
				'location'   => 'right',
				'visibility' => 'hide-mobile-tablet',
			),
		),
	);

	$defaults['bloghash_header_widgets_separator'] 			= 'none';

	$defaults['bloghash_page_header_enable'] 				= false;

	$defaults['bloghash_main_nav_hover_animation'] 			= 'squareboxtwo';

	$defaults['bloghash_breadcrumbs_position']   			= 'below-header';
	$defaults['bloghash_breadcrumbs_background'] 			= bloghash_design_options_defaults(
		array(
			'background' => array(
				'color'    => array(
					'background-color' => '#fff9f3',
				),
				'gradient' => array(),
				'image'    => array(),
			),
		)
	);
	$defaults['bloghash_breadcrumbs_border']     			= bloghash_design_options_defaults(
		array(
			'border' => array(
				'border-top-width'    => 1,
				'border-bottom-width' => 1,
				'border-style'        => 'solid',
				'border-color'        => '#ffe7d2',
			),
		)
	);

	$defaults['bloghash_sidebar_style'] 					= '2';
	$defaults['bloghash_sidebar_width'] 					= 30;
	$defaults['bloghash_sidebar_widget_title_font_size']    = array(
		'desktop' => 1.8,
		'unit'    => 'rem',
	);

	$defaults['bloghash_blog_entry_meta_elements']  		= array(
		'author'   => true,
		'date'     => true,
		'category' => false,
		'tag'      => false,
		'comments' => false,
	);
	$defaults['bloghash_excerpt_length'] 					= 15;
	$defaults['bloghash_blog_horizontal_read_more'] 		= true;

	$defaults['bloghash_enable_ticker'] 					= false;
	$defaults['bloghash_enable_hero']   					= true;
	$defaults['bloghash_hero_type']                       	= 'six-slider';
	$defaults['bloghash_hero_slider_align']					= 'end';
	$defaults['bloghash_hero_slider_overlay']				= 1;
	$defaults['bloghash_hero_slider_column']				= 1;
	$defaults['bloghash_hero_slider_height']                = array(
		'desktop' => 590,
	);
	$defaults['bloghash_hero_slider_title_font_size']     	= array(
		'desktop' => 4.2,
		'unit'    => 'rem',
	);

	$defaults['bloghash_featured_links_type']				= 'two';
	$defaults['bloghash_featured_links_card_shadow']		= false;
	$defaults['bloghash_pyml_card_shadow']					= false;

	$defaults['bloghash_boxed_content_background_color'] 	= '#ffffff';

	$defaults['bloghash_footer_layout']               		= 'layout-2';
	$defaults['bloghash_footer_widget_heading_style'] 		= '3';
	$defaults['bloghash_footer_background']           		= bloghash_design_options_defaults(
		array(
			'background' => array(
				'color'    => array(
					'background-color' => '#f2f2f1',
				),
				'gradient' => array(),
				'image'    => array(),
			),
		)
	);
	$defaults['bloghash_footer_text_color']           		= bloghash_design_options_defaults(
		array(
			'color' => array(
				'text-color'         => '#363533',
				'link-color'         => '#0a0a0a',
				'link-hover-color'   => '#525CEB',
				'widget-title-color' => '#0a0a0a',
			),
		)
	);
	$defaults['bloghash_footer_border']               		= bloghash_design_options_defaults(
		array(
			'border' => array(
				'border-top-width'    => 1,
				'border-bottom-width' => 0,
				'border-color'        => '#ffe7d2',
				'border-style'        => 'solid',
			),
		)
	);

	$defaults['bloghash_copyright_separator'] 				= 'contained-separator';

	$defaults['bloghash_copyright_background'] 				= bloghash_design_options_defaults(
		array(
			'background' => array(
				'color'    => array(
					'background-color' => '#f2f2f1',
				),
				'gradient' => array(),
				'image'    => array(),
			),
		)
	);
	
	$defaults['bloghash_copyright_text_color'] 				= bloghash_design_options_defaults(
		array(
			'color' => array(
				'text-color'       => '#363533',
				'link-color'       => '#0a0a0a',
				'link-hover-color' => '#525CEB',
			),
		)
	);

	// Add categories color options
	$categories                        						= get_categories( array( 'hide_empty' => 1 ) );
	$bloghash_categories_color_options 						= array();
	foreach ( $categories as $category ) {
		$bloghash_categories_color_options[ 'bloghash_category_color_' . $category->term_id ] = '#525CEB';
	}
	$defaults 												= array_merge( $defaults, $bloghash_categories_color_options );

	return $defaults;
}
