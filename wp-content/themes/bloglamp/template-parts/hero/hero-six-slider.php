<?php
/**
 * The template for displaying Hero Six Slider.
 *
 * @package     Bloglamp
 * @author      Peregrine Themes
 * @since       1.0.0
 */


// Setup Hero posts.
$bloghash_hero_slider_orderby = bloghash_option( 'hero_slider_orderby' );
$bloghash_hero_slider_order   = explode( '-', $bloghash_hero_slider_orderby );

$bloghash_args = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => bloghash_option( 'hero_slider_post_number' ), // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
	'order'               => $bloghash_hero_slider_order[1],
	'orderby'             => $bloghash_hero_slider_order[0],
	'ignore_sticky_posts' => true,
	'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		array(
			'taxonomy' => 'post_format',
			'field'    => 'slug',
			'terms'    => array( 'post-format-quote' ),
			'operator' => 'NOT IN',
		),
	),
);

$bloghash_hero_categories = bloghash_option( 'hero_slider_category' );

if ( ! empty( $bloghash_hero_categories ) ) {
	$bloghash_args['category_name'] = implode( ', ', $bloghash_hero_categories );
}

$bloghash_args = apply_filters( 'bloghash_hero_slider_query_args', $bloghash_args );

$bloghash_posts = new WP_Query( $bloghash_args );

// No posts found.
if ( ! $bloghash_posts->have_posts() ) {
	return;
}

$bloghash_hero_items_html = '';

$bloghash_hero_elements       = (array) bloghash_option( 'hero_slider_elements' );
$bloghash_hero_readmore       = isset( $bloghash_hero_elements['read_more'] ) && $bloghash_hero_elements['read_more'] ? ' bloghash-hero-readmore' : '';
$bloghash_hero_read_more_text = bloghash_option( 'hero_slider_read_more' );

// Hero container.
$bloghash_hero_container = bloghash_option( 'hero_slider_container' );
$bloghash_hero_container = 'full-width' === $bloghash_hero_container ? 'bloghash-container bloghash-container__wide' : 'bloghash-container';

$bloghash_hero_slider_align = bloghash_option( 'hero_slider_align' );

$bloghash_classes = array(
    $bloghash_hero_slider_align,
    $bloghash_hero_readmore
);

$bloghash_classes     	= implode( ' ', $bloghash_classes );

while ( $bloghash_posts->have_posts() ) :
	$bloghash_posts->the_post();

	// Post items HTML markup.
	ob_start();
	?>
	<div class="swiper-slide">
		<div class="bloghash-post-item style-1 rounded <?php echo esc_attr( $bloghash_classes ); ?>">
			<div class="bloghash-hero-container <?php echo esc_attr( $bloghash_hero_container ); ?>">
				<div class="bloghash-flex-row">
					<div class="col-xs-12">
						<div class="bloghash-post-content">
							
							<?php if ( isset( $bloghash_hero_elements['category'] ) && $bloghash_hero_elements['category'] ) { ?>
								<div class="post-category">
								<?php bloghash_entry_meta_category( ' ', false, apply_filters( 'bloghash_hero_two_category_limit', 3 ) ); ?>
								</div>
							<?php } ?>

							<?php get_template_part( 'template-parts/entry/entry-header' ); ?>

							<?php get_template_part( 'template-parts/entry/entry-summary' ); ?>

							<?php if ( isset( $bloghash_hero_elements['meta'] ) && $bloghash_hero_elements['meta'] ) { ?>
								<?php
									get_template_part( 'template-parts/entry/entry', 'meta', array( 'bloghash_meta_callback' => 'bloghash_get_hero_entry_meta_elements' ) );
								?>
								<!-- END .entry-meta -->
							<?php } ?>

							<?php if ( $bloghash_hero_readmore ) { ?>
								<a href="<?php echo esc_url( bloghash_entry_get_permalink() ); ?>" class="read-more bloghash-btn btn-outline btn-white btn-uppercase" role="button"><span><?php echo esc_html( $bloghash_hero_read_more_text ); ?></span></a>
							<?php } ?>

						</div><!-- END .bloghash-post-content -->
					</div>
				</div>
			</div>
			<div class="bloghash-post-thumb">
				<a href="<?php echo esc_url( bloghash_entry_get_permalink() ); ?>" tabindex="0"></a>
				<div class="inner"><?php the_post_thumbnail( 'full' ); ?></div>
			</div><!-- END .bloghash-post-thumb -->			
		</div>
	</div>
	<?php
	$bloghash_hero_items_html .= ob_get_clean();
endwhile;

// Restore original Post Data.
wp_reset_postdata();

// Hero overlay.
$bloghash_hero_overlay = absint( bloghash_option( 'hero_slider_overlay' ) );

$bloghash_hero_column = bloghash_option( 'hero_slider_column' );
?>

<div class="bloghash-hero-slider six-slider slider-overlay-<?php echo esc_attr( $bloghash_hero_overlay ); ?>">
	<div class="bloghash-hero-container <?php echo esc_attr( $bloghash_hero_container ); ?>">
		<div class="bloghash-swiper swiper" data-swiper-options='{
				"breakpoints": {
					"0": {
						"spaceBetween": 10,
						"slidesPerView": 1
					},
					"768": {
						"spaceBetween": 16,
						"slidesPerView": 1
					},
					"1200": {
						"spaceBetween": 24,
						"slidesPerView": <?php echo esc_attr( $bloghash_hero_column ); ?>
					}
				},
				"loop": true,
				"loopAdditionalSlides": 3,
				"loopedSlides": 3,
				"centerSlide": true,
				"slideToClickedSlide": true,
				"autoplay": {"delay": 8000, "disableOnInteraction": false},
				"speed": 1000,
				"navigation": {"nextEl": ".hero-next", "prevEl": ".hero-prev"}
			}'>
			<div class="swiper-wrapper">
				<?php echo wp_kses( $bloghash_hero_items_html, bloghash_get_allowed_html_tags() ); ?>
			</div>
			<!-- Add Arrows -->
			<div class="swiper-button-next hero-next"></div>
			<div class="swiper-button-prev hero-prev"></div>
			<div class="swiper-pagination"></div>
		</div>

		<div class="bloghash-spinner visible">
			<div></div>
			<div></div>
		</div>
	</div>
</div><!-- END .bloghash-hero-slider -->
