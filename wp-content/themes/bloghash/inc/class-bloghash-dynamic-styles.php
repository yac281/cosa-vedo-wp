<?php

/**
 * Dynamically generate CSS code.
 * The code depends on options set in the Highend Options and Post/Page metaboxes.
 *
 * If possible, write the dynamically generated code into a .css file, otherwise return the code. The file is refreshed on each modification of metaboxes & theme options.
 *
 * @package     Bloghash
 * @author      Peregrine Themes
 * @since       1.0.0
 */

/**
 * Do not allow direct script access.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bloghash_Dynamic_Styles' ) ) :
	/**
	 * Dynamically generate CSS code.
	 */
	class Bloghash_Dynamic_Styles {

		/**
		 * Singleton instance of the class.
		 *
		 * @since 1.0.0
		 * @var object
		 */
		private static $instance;

		/**
		 * URI for Dynamic CSS file.
		 *
		 * @since 1.0.0
		 * @var object
		 */
		private $dynamic_css_uri;

		/**
		 * Path for Dynamic CSS file.
		 *
		 * @since 1.0.0
		 * @var object
		 */
		private $dynamic_css_path;

		/**
		 * Main Bloghash_Dynamic_Styles Instance.
		 *
		 * @since 1.0.0
		 * @return Bloghash_Dynamic_Styles
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Bloghash_Dynamic_Styles ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Primary class constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			$upload_dir = wp_upload_dir();

			$this->dynamic_css_uri  = trailingslashit( set_url_scheme( $upload_dir['baseurl'] ) ) . 'bloghash/';
			$this->dynamic_css_path = trailingslashit( set_url_scheme( $upload_dir['basedir'] ) ) . 'bloghash/';

			if ( ! is_customize_preview() && wp_is_writable( trailingslashit( $upload_dir['basedir'] ) ) ) {
				add_action( 'bloghash_enqueue_scripts', array( $this, 'enqueue_dynamic_style' ), 20 );
			} else {
				add_action( 'bloghash_enqueue_scripts', array( $this, 'print_dynamic_style' ), 99 );
			}

			// Include button styles.
			add_filter( 'bloghash_dynamic_styles', array( $this, 'get_button_styles' ), 6 );

			// Remove Customizer Custom CSS from wp_head, we will include it in our dynamic file.
			if ( ! is_customize_preview() ) {
				remove_action( 'wp_head', 'wp_custom_css_cb', 101 );
			}

			// Generate new styles on Customizer Save action.
			add_action( 'customize_save_after', array( $this, 'update_dynamic_file' ) );

			// Generate new styles on theme activation.
			add_action( 'after_switch_theme', array( $this, 'update_dynamic_file' ) );

			// Delete the css stye on theme deactivation.
			add_action( 'switch_theme', array( $this, 'delete_dynamic_file' ) );

			// Generate initial dynamic css.
			add_action( 'init', array( $this, 'init' ) );
		}

		/**
		 * Init.
		 *
		 * @since 1.0.0
		 */
		public function init() {

			// Ensure we have dynamic stylesheet generated.
			if ( false === get_transient( 'bloghash_has_dynamic_css' ) ) {
				$this->update_dynamic_file();
			}
		}

		/**
		 * Enqueues dynamic styles file.
		 *
		 * @since 1.0.0
		 */
		public function enqueue_dynamic_style() {

			$exists = file_exists( $this->dynamic_css_path . 'dynamic-styles.css' );
			// Generate the file if it's missing.
			if ( ! $exists ) {
				$exists = $this->update_dynamic_file();
			}

			// Enqueue the file if available.
			if ( $exists ) {
				wp_enqueue_style(
					'bloghash-dynamic-styles',
					$this->dynamic_css_uri . 'dynamic-styles.css',
					false,
					filemtime( $this->dynamic_css_path . 'dynamic-styles.css' ),
					'all'
				);
			}
		}

		/**
		 * Prints inline dynamic styles if writing to file is not possible.
		 *
		 * @since 1.0.0
		 */
		public function print_dynamic_style() {
			$dynamic_css = $this->get_css();
			wp_add_inline_style( 'bloghash-styles', $dynamic_css );
		}

		/**
		 * Generates dynamic CSS code, minifies it and cleans cache.
		 *
		 * @param  boolean $custom_css - should we include the wp_get_custom_css.
		 * @return string, minifed code
		 * @since  1.0.0
		 */
		public function get_css( $custom_css = false ) {

			// Refresh options.
			bloghash()->options->refresh();

			// Delete google fonts enqueue transients.
			delete_transient( 'bloghash_google_fonts_enqueue' );

			// Add our theme custom CSS.
			$css = '';

			// Dark Mode.
			if ( is_array( $header_active_widgets = bloghash_option( 'header_widgets' ) ) ) {

				array_walk(
					$header_active_widgets,
					function ( $value, $key ) use ( &$css ) {
						$darkMode = bloghash_option( 'dark_mode' );
						if ( $value['type'] == 'darkmode' || $darkMode ) {
							$css = '
							[data-darkmode=dark]:root {
								--bloghash-white: ' . bloghash_sanitize_color( bloghash_option( 'headings_color' ) ) . ';
								--bloghash-secondary: #fff;
							}
							[data-darkmode=dark] select option {
								background: rgba(0, 0, 0, 0.3);
  								color: #fff;
							}
							[data-darkmode=dark] .entry-media > a .entry-media-icon .bloghash-icon,
							[data-darkmode=dark] .entry-media > a .entry-media-icon svg,
							[data-darkmode=dark] #bloghash-scroll-top svg,
							[data-darkmode=dark] .navigation .nav-links .page-numbers svg,
							[data-darkmode=dark] .navigation .nav-links .page-numbers:hover svg,
							[data-darkmode=dark] .using-keyboard .navigation .nav-links .page-numbers:focus svg {
								fill: #fff;
							}
							[data-darkmode=dark] .bloghash-post-item .bloghash-post-content .bloghash-btn,
							[data-darkmode=dark] .wp-block-search .wp-block-search__button {
								--bloghash-white: #fff;
								--bloghash-secondary: #000;
							}
							[data-darkmode=dark] .bloghash-btn.btn-white.btn-outline:hover,
							[data-darkmode=dark] .using-keyboard .bloghash-btn.btn-white.btn-outline:focus {
								--bloghash-secondary: #fff;
							}
							[data-darkmode=dark] #comments a,
							[data-darkmode=dark] #colophon .search-form .search-submit,
							[data-darkmode=dark] #main .search-form .search-submit,
							[data-darkmode=dark] .content-area a:not(.bloghash-btn,.showcoupon,.wp-block-button__link):hover,
							[data-darkmode=dark] #secondary .hester-core-custom-list-widget .bloghash-entry a:not(.bloghash-btn):hover,
							[data-darkmode=dark] .bloghash-breadcrumbs a:hover,
							[data-darkmode=dark] #add_payment_method table.cart td.actions .coupon .input-text:focus,
							[data-darkmode=dark] .woocommerce-cart table.cart td.actions .coupon .input-text:focus,
							[data-darkmode=dark] .woocommerce-checkout table.cart td.actions .coupon .input-text:focus,
							[data-darkmode=dark] .woocommerce div.product #reviews #comments ol.commentlist li .comment-text p.meta strong,
							[data-darkmode=dark] input[type="date"]:focus,
							[data-darkmode=dark] input[type="email"]:focus,
							[data-darkmode=dark] input[type="password"]:focus,
							[data-darkmode=dark] input[type="search"]:focus,
							[data-darkmode=dark] input[type="tel"]:focus,
							[data-darkmode=dark] input[type="text"]:focus,
							[data-darkmode=dark] input[type="url"]:focus,
							[data-darkmode=dark] textarea:focus,
							[data-darkmode=dark] .entry-media > a .entry-media-icon .bloghash-icon,
							[data-darkmode=dark] .entry-media > a .entry-media-icon svg,
							[data-darkmode=dark] .navigation .nav-links .page-numbers:hover button,
							[data-darkmode=dark] .using-keyboard .navigation .nav-links .page-numbers:focus button,
							[data-darkmode=dark] .navigation .nav-links .page-numbers:not(.prev, .next).current,
							[data-darkmode=dark] .navigation .nav-links .page-numbers:not(.prev, .next):hover,
							[data-darkmode=dark] .using-keyboard .navigation .nav-links .page-numbers:not(.prev, .next):focus,
							[data-darkmode=dark] .page-links a:hover span,
							[data-darkmode=dark] .using-keyboard .page-links a:focus span,
							[data-darkmode=dark] .page-links > span,
							[data-darkmode=dark] .bloghash-btn.btn-text-1:hover,
							[data-darkmode=dark] .bloghash-btn.btn-text-1:focus,
							[data-darkmode=dark] .btn-text-1:hover,
							[data-darkmode=dark] .btn-text-1:focus,
							[data-darkmode=dark] .bloghash-header-widgets .bloghash-search-simple .bloghash-search-form button:not(.bloghash-search-close),
							[data-darkmode=dark] #bloghash-header,
							[data-darkmode=dark] .bloghash-header-widgets a:not(.bloghash-btn),
							[data-darkmode=dark] .bloghash-logo a,
							[data-darkmode=dark] .bloghash-hamburger,
							[data-darkmode=dark] h1,
							[data-darkmode=dark] h2,
							[data-darkmode=dark] h3,
							[data-darkmode=dark] h4,
							[data-darkmode=dark] h5,
							[data-darkmode=dark] h6,
							[data-darkmode=dark] .h1,
							[data-darkmode=dark] .h2,
							[data-darkmode=dark] .h3,
							[data-darkmode=dark] .h4,
							[data-darkmode=dark] .bloghash-logo .site-title,
							[data-darkmode=dark] .error-404 .page-header h1,
							[data-darkmode=dark] body,
							[data-darkmode=dark] #bloghash-header .bloghash-nav li > a {
								color: #fff;
							}
							[data-darkmode=dark] .woocommerce table.my_account_orders thead th,
							[data-darkmode=dark] .woocommerce table.woocommerce-table--order-downloads thead th,
							[data-darkmode=dark] .woocommerce table.woocommerce-table--order-details thead th,
							[data-darkmode=dark] .bloghash-cart-item .bloghash-x,
							[data-darkmode=dark] .woocommerce form.login .lost_password a,
							[data-darkmode=dark] .woocommerce form.register .lost_password a,
							[data-darkmode=dark] .woocommerce a.remove,
							[data-darkmode=dark] #add_payment_method .cart-collaterals .cart_totals .woocommerce-shipping-destination,
							[data-darkmode=dark] .woocommerce-cart .cart-collaterals .cart_totals .woocommerce-shipping-destination,
							[data-darkmode=dark] .woocommerce-checkout .cart-collaterals .cart_totals .woocommerce-shipping-destination,
							[data-darkmode=dark] .woocommerce ul.products li.product .bloghash-loop-product__category-wrap a,
							[data-darkmode=dark] .woocommerce ul.products li.product .bloghash-loop-product__category-wrap,
							[data-darkmode=dark] .woocommerce .woocommerce-checkout-review-order table.shop_table thead th,
							[data-darkmode=dark] #add_payment_method #payment div.payment_box,
							[data-darkmode=dark] .woocommerce-cart #payment div.payment_box,
							[data-darkmode=dark] .woocommerce-checkout #payment div.payment_box,
							[data-darkmode=dark] #add_payment_method #payment ul.payment_methods .about_paypal,
							[data-darkmode=dark] .woocommerce-cart #payment ul.payment_methods .about_paypal,
							[data-darkmode=dark] .woocommerce-checkout #payment ul.payment_methods .about_paypal,
							[data-darkmode=dark] .woocommerce table dl,
							[data-darkmode=dark] .woocommerce table .wc-item-meta,
							[data-darkmode=dark] .widget.woocommerce .reviewer,
							[data-darkmode=dark] .woocommerce.widget_shopping_cart .cart_list li a.remove::before,
							[data-darkmode=dark] .woocommerce .widget_shopping_cart .cart_list li a.remove::before,
							[data-darkmode=dark] .woocommerce .widget_shopping_cart .cart_list li .quantity,
							[data-darkmode=dark] .woocommerce.widget_shopping_cart .cart_list li .quantity,
							[data-darkmode=dark] .woocommerce div.product .woocommerce-product-rating .woocommerce-review-link,
							[data-darkmode=dark] .woocommerce div.product .woocommerce-tabs table.shop_attributes td,
							[data-darkmode=dark] .woocommerce div.product .product_meta > span span:not(.bloghash-woo-meta-title),
							[data-darkmode=dark] .woocommerce div.product .product_meta > span a,
							[data-darkmode=dark] .woocommerce .star-rating::before,
							[data-darkmode=dark] .woocommerce div.product #reviews #comments ol.commentlist li .comment-text p.meta,
							[data-darkmode=dark] .ywar_review_count,
							[data-darkmode=dark] .woocommerce .add_to_cart_inline del,
							[data-darkmode=dark] .woocommerce div.product p.price del,
							[data-darkmode=dark] .woocommerce div.product span.price del,
							[data-darkmode=dark] .woocommerce #yith-wcwl-form table.shop_table thead,
							[data-darkmode=dark] .woocommerce .woocommerce-cart-form table.shop_table thead,
							[data-darkmode=dark] .woocommerce .woocommerce-checkout-review-order table.shop_table thead,
							[data-darkmode=dark] .woocommerce div.product .woocommerce-tabs ul.tabs li a,
							[data-darkmode=dark] .woocommerce-message,
							[data-darkmode=dark] .woocommerce-error,
							[data-darkmode=dark] .woocommerce-info,
							[data-darkmode=dark] .woocommerce-message,
							[data-darkmode=dark] .woocommerce div.product .woocommerce-tabs ul.tabs li:not(.active) a:hover,
							[data-darkmode=dark] .woocommerce div.product .woocommerce-tabs ul.tabs li:not(.active) a:focus,
							[data-darkmode=dark] .woocommerce #yith-wcwl-form table.shop_table,
							[data-darkmode=dark] .woocommerce .woocommerce-cart-form table.shop_table,
							[data-darkmode=dark] .woocommerce .woocommerce-checkout-review-order table.shop_table,
							[data-darkmode=dark] .bloghash-btn.btn-text-1,
							[data-darkmode=dark] .btn-text-1,
							[data-darkmode=dark] .comment-form .comment-notes,
							[data-darkmode=dark] #comments .no-comments,
							[data-darkmode=dark] #page .wp-caption .wp-caption-text,
							[data-darkmode=dark] #comments .comment-meta,
							[data-darkmode=dark] .comments-closed,
							[data-darkmode=dark] .entry-meta,
							[data-darkmode=dark] .bloghash-entry cite,
							[data-darkmode=dark] legend,
							[data-darkmode=dark] .bloghash-page-header-description,
							[data-darkmode=dark] .page-links em,
							[data-darkmode=dark] .site-content .page-links em,
							[data-darkmode=dark] .single .entry-footer .last-updated,
							[data-darkmode=dark] .single .post-nav .post-nav-title,
							[data-darkmode=dark] #main .widget_recent_comments span,
							[data-darkmode=dark] #main .widget_recent_entries span,
							[data-darkmode=dark] #main .widget_calendar table > caption,
							[data-darkmode=dark] .post-thumb-caption,
							[data-darkmode=dark] .wp-block-image figcaption,
							[data-darkmode=dark] .wp-block-embed figcaption {
								color: rgba(255,255,255,0.7);
							}
							[data-darkmode=dark] #bloghash-header .bloghash-nav .children li.current_page_ancestor > a,
							[data-darkmode=dark] #bloghash-header .bloghash-nav .children li.current_page_item > a,
							[data-darkmode=dark] #bloghash-header .bloghash-nav .children li:hover > a,
							[data-darkmode=dark] #bloghash-header .bloghash-nav .sub-menu li.current-menu-ancestor > a,
							[data-darkmode=dark] #bloghash-header .bloghash-nav .sub-menu li.current-menu-item > a,
							[data-darkmode=dark] #bloghash-header .bloghash-nav .sub-menu li:hover > a {
								color: rgba(255,255,255,0.7) !important;
							}
							[data-darkmode=dark] .entry-meta .entry-meta-elements > span::before {
								background-color: rgba(255, 255, 255, 0.25);
							}

							[data-darkmode=dark] .bloghash-post-gallery .swiper-button-prev,
							[data-darkmode=dark] .bloghash-post-gallery .swiper-button-next,
							[data-darkmode=dark] .bloghash-vertical-slider .swiper-button-prev,
							[data-darkmode=dark] .bloghash-vertical-slider .swiper-button-next,
							[data-darkmode=dark] .bloghash-horizontal-slider .swiper-button-prev,
							[data-darkmode=dark] .bloghash-horizontal-slider .swiper-button-next,
							[data-darkmode=dark] .woocommerce #yith-wcwl-form table.shop_table th:first-child,
							[data-darkmode=dark] .woocommerce #yith-wcwl-form table.shop_table td:first-child,
							[data-darkmode=dark] .woocommerce .woocommerce-cart-form table.shop_table th:first-child,
							[data-darkmode=dark] .woocommerce .woocommerce-cart-form table.shop_table td:first-child,
							[data-darkmode=dark] .woocommerce .woocommerce-checkout-review-order table.shop_table th:first-child,
							[data-darkmode=dark] .woocommerce .woocommerce-checkout-review-order table.shop_table td:first-child,
							[data-darkmode=dark] .woocommerce #yith-wcwl-form table.shop_table td,
							[data-darkmode=dark] .woocommerce .woocommerce-cart-form table.shop_table td,
							[data-darkmode=dark] .woocommerce .woocommerce-checkout-review-order table.shop_table td,
							[data-darkmode=dark] .woocommerce #yith-wcwl-form table.shop_table tr:nth-last-child(2) td,
							[data-darkmode=dark] .woocommerce .woocommerce-cart-form table.shop_table tr:nth-last-child(2) td,
							[data-darkmode=dark] .woocommerce .cart_totals table.shop_table,
							[data-darkmode=dark] .woocommerce .cart_totals table.shop_table th,
							[data-darkmode=dark] .woocommerce .cart_totals table.shop_table td,
							[data-darkmode=dark] .bloghash-header-layout-5 #masthead+#main .bloghash-breadcrumbs,
							[data-darkmode=dark] #bloghash-header-inner,
							[data-darkmode=dark] .page-header {
								border-color: rgba(255,255,255,0.08);
							}
							html[data-darkmode=dark] body,
							[data-darkmode=dark] .select2-dropdown,
							[data-darkmode=dark] .bloghash-header-layout-5 #masthead+#main .bloghash-breadcrumbs,
							[data-darkmode=dark] #add_payment_method #payment ul.payment_methods li:not(.woocommerce-notice),
							[data-darkmode=dark] .woocommerce-cart #payment ul.payment_methods li:not(.woocommerce-notice),
							[data-darkmode=dark] .woocommerce-checkout #payment ul.payment_methods li:not(.woocommerce-notice),
							html[data-darkmode=dark] .woocommerce div.product .woocommerce-tabs table.shop_attributes,
							[data-darkmode=dark] .bloghash-header-layout-6 .bloghash-nav-container,
							[data-darkmode=dark] .bloghash-header-layout-4 .bloghash-nav-container,
							[data-darkmode=dark] .bloghash-header-layout-3 .bloghash-nav-container,
							[data-darkmode=dark] #bloghash-header-inner {
								background: ' . bloghash_sanitize_color( bloghash_option( 'headings_color' ) ) . ';
							}
							[data-darkmode=dark] .page-header,
							[data-darkmode=dark] .select2-container--default .select2-selection--single,
							[data-darkmode=dark] .woocommerce .woocommerce-checkout-review-order table.shop_table,
							[data-darkmode=dark] .woocommerce #yith-wcwl-form table.shop_table thead th,
							[data-darkmode=dark] .woocommerce .woocommerce-cart-form table.shop_table thead th,
							[data-darkmode=dark] .woocommerce .woocommerce-checkout-review-order table.shop_table thead th,
							[data-darkmode=dark] .woocommerce .cart_totals table.shop_table .order-total th,
							[data-darkmode=dark] .woocommerce .cart_totals table.shop_table .order-total td,
							[data-darkmode=dark] .woocommerce div.product .woocommerce-tabs .wc-tab,
							[data-darkmode=dark] .woocommerce div.product #reviews #comments .comment_container,
							[data-darkmode=dark] #page .woocommerce-error,
							[data-darkmode=dark] #page .woocommerce-info,
							[data-darkmode=dark] #page .woocommerce-message,
							[data-darkmode=dark] .woocommerce div.product .woocommerce-tabs ul.tabs::before,
							[data-darkmode=dark] .woocommerce div.product .woocommerce-tabs ul.tabs::after,
							[data-darkmode=dark] .bloghash-layout__boxed-separated .ticker-slider-items,
							[data-darkmode=dark] .bloghash-card__boxed .bloghash-card-items,
							[data-darkmode=dark] .bloghash-layout__framed #page,
							[data-darkmode=dark] .bloghash-layout__boxed #page,
							[data-darkmode=dark] .bloghash-layout__boxed-separated:not(.blog, .archive, .category, .search-results) #comments,
							[data-darkmode=dark] .bloghash-layout__boxed-separated:not(.blog, .archive, .category, .search-results) #content > article,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.author .author-box,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.single #content > article,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.bloghash-sidebar-style-2 #secondary .bloghash-widget,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.bloghash-sidebar-style-2 .elementor-widget-sidebar .bloghash-widget,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.page .bloghash-article,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.archive .bloghash-article,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.blog .bloghash-article,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.search-results .bloghash-article,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.category .bloghash-article {
								background-color: rgba(0,0,0,0.3);
							}
							[data-darkmode=dark] .woocommerce ul.products li.product:hover,
							[data-darkmode=dark] .woocommerce ul.products li.product:focus-within,
							[data-darkmode=dark] .bloghash-layout__framed #page,
							[data-darkmode=dark] .bloghash-layout__boxed #page {
								-webkit-box-shadow: 0 0 3.5rem rgba(0, 0, 0, 0.4);
								box-shadow: 0 0 3.5rem rgba(0, 0, 0, 0.4);
							}
							[data-darkmode=dark] .bloghash-btn.btn-text-1 > span::before {
								background-color: #fff;
							}
							[data-darkmode=dark] .woocommerce .quantity .bloghash-woo-minus:not(:hover, :focus),
							[data-darkmode=dark] .woocommerce .quantity .bloghash-woo-plus:not(:hover, :focus) {
								color: ' . bloghash_sanitize_color( bloghash_option( 'headings_color' ) ) . ' !important;
							}
							[data-darkmode=dark] .bloghash-card__boxed .bloghash-card-items,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.bloghash-sidebar-style-2 #secondary .bloghash-widget,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.archive article.bloghash-article,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.blog article.bloghash-article,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.category article.bloghash-article,
							[data-darkmode=dark] .bloghash-layout__boxed-separated.search-results article.bloghash-article {
								border-color: rgba(190,190,190,0.30);
							}
							[data-darkmode=dark] .bloghash-social-nav.rounded > ul > li > a > span:not(.screen-reader-text) {
								background-color: rgba(190,190,190,0.30);
							}
							[data-darkmode=dark] .bloghash-blog-layout-1 .bloghash-article .entry-thumb-image .entry-media {
								background-color: rgba(39,39,39,.75);
							}
							@media screen and (max-width: ' . intval( bloghash_option( 'main_nav_mobile_breakpoint' ) ) . 'px) {
								[data-darkmode=dark] .bloghash-layout__boxed-separated #page {
									background-color: rgba(0,0,0,0.3);
								}
								[data-darkmode=dark] #bloghash-header-inner .site-navigation > ul li {
									border-bottom-color: rgba(255,255,255,0.08);
								}
								[data-darkmode=dark] #bloghash-header-inner .site-navigation {
									background: ' . bloghash_sanitize_color( bloghash_option( 'headings_color' ) ) . ';
								}
								[data-darkmode=dark] .bloghash-mobile-toggen,
								[data-darkmode=dark] #bloghash-header-inner .bloghash-nav {
									color: rgba(255,255,255,0.7);
								}
								[data-darkmode=dark] #bloghash-header-inner .bloghash-nav .menu-item-has-children > a > span,
								[data-darkmode=dark] #bloghash-header-inner .bloghash-nav .page_item_has_children > a > span {
									border-right-color: rgba(255,255,255,0.08);
								}
								[data-darkmode=dark] #bloghash-header-inner .site-navigation > ul .sub-menu {
									background: rgba(0,0,0,0.3);
								}
							}
							';
						}
					}
				);
			}

			// Category color.
			$categories = get_categories( array( 'hide_empty' => 1 ) );
			foreach ( $categories as $category ) {
				$term_id   = $category->term_id;
				$cat_color = bloghash_option( 'category_color_' . strtolower( $term_id ) );

				$css .= '
				.post-category .cat-links a.cat-' . absint( $term_id ) . ' {
					--bloghash-primary: ' . bloghash_sanitize_color( $cat_color ) . ';
					--bloghash-primary_80: ' . bloghash_sanitize_color( bloghash_luminance( $cat_color, .80 ) ) . ';
				}';
			}

			// Accent color.
			$accent_color = bloghash_option( 'accent_color' );

			$css .= '
				:root {
					--bloghash-primary: ' . bloghash_sanitize_color( $accent_color ) . ';
					--bloghash-primary_80: ' . bloghash_sanitize_color( bloghash_luminance( $accent_color, .80 ) ) . ';
					--bloghash-primary_15: ' . bloghash_sanitize_color( bloghash_luminance( $accent_color, .15 ) ) . ';
					--bloghash-primary_27: ' . bloghash_sanitize_color( bloghash_hex2rgba( $accent_color, .27 ) ) . ';
					--bloghash-primary_10: ' . bloghash_sanitize_color( bloghash_hex2rgba( $accent_color, .10 ) ) . ';
				}
			';

			if ( bloghash_option( 'blog_zig_zag' ) ) {
				$css .= '.bloghash-blog-horizontal .col-xs-12:nth-child(even) .bloghash-article:not(.format-quote) .bloghash-blog-entry-wrapper {
					flex-direction: row-reverse;
				}
				@media only screen and (min-width: 869px) {
					.bloghash-blog-horizontal .col-xs-12:nth-child(even) .bloghash-article:not(.format-quote) .bloghash-blog-entry-wrapper.bloghash-thumb-left .entry-media {
						margin-left: 3rem;
						margin-right: 0;
					}
				}';
			}

			$header_layout_3_additional_css = '';

			if ( 'layout-3' === bloghash_option( 'header_layout' ) || is_customize_preview() ) {
				$header_layout_3_additional_css = '

					.bloghash-header-layout-3 .bloghash-logo-container > .bloghash-container {
						flex-wrap: wrap;
					}

					.bloghash-header-layout-3 .bloghash-logo-container .bloghash-logo > .logo-inner {
						align-items: flex-start;
					}
					
					.bloghash-header-layout-3 .bloghash-logo-container .bloghash-logo {
						order: 0;
						align-items: flex-start;
						flex-basis: auto;
						margin-left: 0;
					}

					.bloghash-header-layout-3 .bloghash-logo-container .bloghash-header-element {
						flex-basis: auto;
					}

					.bloghash-header-layout-3 .bloghash-logo-container .bloghash-mobile-nav {
						order: 5;
					}

					.bloghash-header-layout-3 .bloghash-widget-location-left .dropdown-item {
						left: auto;
						right: -7px;
					}

					.bloghash-header-layout-3 .bloghash-widget-location-left .dropdown-item::after {
						left: auto;
						right: 8px;
					}

				';
			}

			$header_layout_4_additional_css = '';

			if ( 'layout-4' === bloghash_option( 'header_layout' ) || is_customize_preview() ) {
				$header_layout_4_additional_css = '

					.bloghash-header-layout-4 .bloghash-logo-container > .bloghash-container {
						flex-wrap: wrap;
					}

					.bloghash-header-layout-4 .bloghash-logo-container .bloghash-logo > .logo-inner {
						align-items: flex-start;
					}
					
					.bloghash-header-layout-4 .bloghash-logo-container .bloghash-logo {
						order: 0;
						align-items: flex-start;
						flex-basis: auto;
						margin-left: 0;
					}

					.bloghash-header-layout-4 .bloghash-logo-container .bloghash-header-element {
						flex-basis: auto;
					}

					.bloghash-header-layout-4 .bloghash-logo-container .bloghash-mobile-nav {
						order: 5;
					}

					.bloghash-header-layout-4 .bloghash-widget-location-left .dropdown-item {
						left: auto;
						right: -7px;
					}

					.bloghash-header-layout-4 .bloghash-widget-location-left .dropdown-item::after {
						left: auto;
						right: 8px;
					}

					.bloghash-header-layout-4 .bloghash-logo-container .bloghash-widget-location-right {
						-js-display: flex;
						display: -webkit-box;
						display: -ms-flexbox;
						display: flex;
					}

					.bloghash-header-layout-4 .bloghash-nav-container .bloghash-header-element {
						display: none;
					}

				';
			}

			$header_layout_6_additional_css = '';

			if ( 'layout-6' === bloghash_option( 'header_layout' ) || is_customize_preview() ) {
				$header_layout_6_additional_css = '

					.bloghash-header-layout-6 .bloghash-logo-container > .bloghash-container {
						flex-wrap: wrap;
					}

					.bloghash-header-layout-6 .bloghash-logo-container .bloghash-logo > .logo-inner {
						align-items: flex-start;
					}
					
					.bloghash-header-layout-6 .bloghash-logo-container .bloghash-logo {
						order: 0;
						align-items: flex-start;
						flex-basis: auto;
						margin-left: 0;
					}

					.bloghash-header-layout-6 .bloghash-logo-container .bloghash-header-element {
						flex-basis: auto;
					}

					.bloghash-header-layout-6 .bloghash-logo-container .bloghash-mobile-nav {
						order: 5;
					}

					.bloghash-header-layout-6 .bloghash-widget-location-left .dropdown-item {
						left: auto;
						right: -7px;
					}

					.bloghash-header-layout-6 .bloghash-widget-location-left .dropdown-item::after {
						left: auto;
						right: 8px;
					}

					.bloghash-header-layout-6 .bloghash-logo-container .bloghash-widget-location-right {
						-js-display: flex;
						display: -webkit-box;
						display: -ms-flexbox;
						display: flex;
					}

					.bloghash-header-layout-6 .bloghash-nav-container .bloghash-header-element {
						display: none;
					}

				';
			}

			/**
			 * Top Bar.
			 */

			// Background.
			$css .= $this->get_design_options_field_css( '#bloghash-topbar', 'top_bar_background', 'background' );

			// Border.
			$css .= $this->get_design_options_field_css( '#bloghash-topbar', 'top_bar_border', 'border' );
			$css .= $this->get_design_options_field_css( '.bloghash-topbar-widget', 'top_bar_border', 'separator_color' );

			// Top Bar colors.
			$topbar_color = bloghash_option( 'top_bar_text_color' );

			// Top Bar text color.
			if ( isset( $topbar_color['text-color'] ) && $topbar_color['text-color'] ) {
				$css .= '#bloghash-topbar { color: ' . bloghash_sanitize_color( $topbar_color['text-color'] ) . '; }';
			}

			// Top Bar link color.
			if ( isset( $topbar_color['link-color'] ) && $topbar_color['link-color'] ) {
				$css .= '
					.bloghash-topbar-widget__text a,
					.bloghash-topbar-widget .bloghash-nav > ul > li > a,
					.bloghash-topbar-widget__socials .bloghash-social-nav > ul > li > a,
					#bloghash-topbar .bloghash-topbar-widget__text .bloghash-icon { 
						color: ' . bloghash_sanitize_color( $topbar_color['link-color'] ) . '; }
				';
			}

			// Top Bar link hover color.
			if ( isset( $topbar_color['link-hover-color'] ) && $topbar_color['link-hover-color'] ) {
				$css .= '
					#bloghash-topbar .bloghash-nav > ul > li > a:hover,
					.using-keyboard #bloghash-topbar .bloghash-nav > ul > li > a:focus,
					#bloghash-topbar .bloghash-nav > ul > li.menu-item-has-children:hover > a,
					#bloghash-topbar .bloghash-nav > ul > li.current-menu-item > a,
					#bloghash-topbar .bloghash-nav > ul > li.current-menu-ancestor > a,
					#bloghash-topbar .bloghash-topbar-widget__text a:hover,
					#bloghash-topbar .bloghash-social-nav > ul > li > a .bloghash-icon.bottom-icon { 
						color: ' . bloghash_sanitize_color( $topbar_color['link-hover-color'] ) . '; }
				';
			}

			/**
			 * Header.
			 */

			// Background.
			$css .= $this->get_design_options_field_css( '#bloghash-header-inner', 'header_background', 'background' );

			// Font colors.
			$header_color = bloghash_option( 'header_text_color' );

			// Header text color.
			if ( isset( $header_color['text-color'] ) && $header_color['text-color'] ) {
				$css .= '.bloghash-logo .site-description { color: ' . bloghash_sanitize_color( $header_color['text-color'] ) . '; }';
			}

			// Header link color.
			if ( isset( $header_color['link-color'] ) && $header_color['link-color'] ) {
				$css .= '
					#bloghash-header,
					.bloghash-header-widgets a:not(.bloghash-btn),
					.bloghash-logo a,
					.bloghash-hamburger { 
						color: ' . bloghash_sanitize_color( $header_color['link-color'] ) . '; }
				';
			}

			// Header link hover color.
			if ( isset( $header_color['link-hover-color'] ) && $header_color['link-hover-color'] ) {
				$css .= '
					.bloghash-header-widgets a:not(.bloghash-btn):hover, 
					#bloghash-header-inner .bloghash-header-widgets .bloghash-active,
					.bloghash-logo .site-title a:hover, 
					.bloghash-hamburger:hover, 
					.is-mobile-menu-active .bloghash-hamburger,
					#bloghash-header-inner .bloghash-nav > ul > li > a:hover,
					#bloghash-header-inner .bloghash-nav > ul > li.menu-item-has-children:hover > a,
					#bloghash-header-inner .bloghash-nav > ul > li.current-menu-item > a,
					#bloghash-header-inner .bloghash-nav > ul > li.current-menu-ancestor > a,
					#bloghash-header-inner .bloghash-nav > ul > li.page_item_has_children:hover > a,
					#bloghash-header-inner .bloghash-nav > ul > li.current_page_item > a,
					#bloghash-header-inner .bloghash-nav > ul > li.current_page_ancestor > a {
						color: ' . bloghash_sanitize_color( $header_color['link-hover-color'] ) . ';
					}
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li > a:hover,
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.menu-item-has-children:hover > a,
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.current-menu-item > a,
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.current-menu-ancestor > a,
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.page_item_has_children:hover > a,
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.current_page_item > a,
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.current_page_ancestor > a {
						color: ' . bloghash_sanitize_color( $header_color['link-hover-color'] ) . ';
						border-color: ' . bloghash_sanitize_color( $header_color['link-hover-color'] ) . ';
					}
				';
			}

			// Header border.
			$css .= $this->get_design_options_field_css( '#bloghash-header-inner', 'header_border', 'border' );

			// Header separator color.
			$css .= $this->get_design_options_field_css( '.bloghash-header-widget', 'header_border', 'separator_color' );

			// Main navigation breakpoint.
			$css .= '
				@media screen and (max-width: ' . intval( bloghash_option( 'main_nav_mobile_breakpoint' ) ) . 'px) {

					#bloghash-header-inner .bloghash-nav {
						display: none;
						color: #000;
					}
					.bloghash-mobile-toggen,
					.bloghash-mobile-nav {
						display: inline-flex;
					}

					#bloghash-header-inner {
						position: relative;
					}

					#bloghash-header-inner .bloghash-nav > ul > li > a {
						color: inherit;
					}

					#bloghash-header-inner .bloghash-nav-container {
						position: static;
						border: none;
					}

					#bloghash-header-inner .site-navigation {
						display: none;
						position: absolute;
						top: 100%;
						width: 100%;
						height: 100%;
						min-height: 100vh;
						left: 0;
						right: 0;
						margin: -1px 0 0;
						background: #FFF;
						border-top: 1px solid #eaeaea;
						box-shadow: 0 15px 25px -10px  rgba(50, 52, 54, 0.125);
						z-index: 999;
						font-size: 1.7rem;
						padding: 0;
					}

					.bloghash-header-layout-5 #bloghash-header-inner .site-navigation {
						min-height: unset;
						border-radius: 15px;
						height: unset;
					}

					#bloghash-header-inner .site-navigation > ul {
						overflow-y: auto;
						max-height: 68vh;
						display: block;
					}

					#bloghash-header-inner .site-navigation > ul > li > a {
						padding: 0 !important;
					}

					#bloghash-header-inner .site-navigation > ul li {
						display: block;
						width: 100%;
						padding: 0;
						margin: 0;
						margin-left: 0 !important;
					}

					#bloghash-header-inner .site-navigation > ul .sub-menu {
						position: static;
						display: none;
						border: none;
						box-shadow: none;
						border: 0;
						opacity: 1;
						visibility: visible;
						font-size: 1.7rem;
						transform: none;
						background: #f8f8f8;
						pointer-events: all;
						min-width: initial;
						left: 0;
						padding: 0;
						margin: 0;
						border-radius: 0;
						line-height: inherit;
					}

					#bloghash-header-inner .site-navigation > ul .sub-menu > li > a > span {
						padding-left: 50px !important;
					}

					#bloghash-header-inner .site-navigation > ul .sub-menu .sub-menu > li > a > span {
						padding-left: 70px !important;
					}

					#bloghash-header-inner .site-navigation > ul .sub-menu a > span {
						padding: 10px 30px 10px 50px;
					}

					#bloghash-header-inner .site-navigation > ul a {
						padding: 0;
						position: relative;
						background: none;
					}

					#bloghash-header-inner .site-navigation > ul li {
						border-bottom: 1px solid #eaeaea;
					}

					#bloghash-header-inner .site-navigation > ul > li:last-child {
						border-bottom: 0;
					}

					#bloghash-header-inner .site-navigation > ul a > span {
						padding: 10px 30px !important;
						width: 100%;
						display: block;
					}

					#bloghash-header-inner .site-navigation > ul a > span::after,
					#bloghash-header-inner .site-navigation > ul a > span::before {
						display: none !important;
					}

					#bloghash-header-inner .site-navigation > ul a > span.description {
						display: none;
					}

					#bloghash-header-inner .site-navigation > ul .menu-item-has-children > a {
						display: inline-flex;
    					width: 100%;
						max-width: calc(100% - 50px);
					}

					#bloghash-header-inner .bloghash-nav .menu-item-has-children>a > span, 
					#bloghash-header-inner .bloghash-nav .page_item_has_children>a > span {
					    border-right: 1px solid rgba(185, 185, 185, 0.4);
					}

					#bloghash-header-inner .bloghash-nav .menu-item-has-children>a > .bloghash-icon, 
					#bloghash-header-inner .bloghash-nav .page_item_has_children>a > .bloghash-icon {
						transform: none;
						width: 50px;
					    margin: 0;
					    position: absolute;
					    right: 0;
					    pointer-events: none;
					    height: 1em;
						display: none;
					}

					.bloghash-nav .sub-menu li.current-menu-item > a {
						font-weight: 500;
					}

					.bloghash-mobile-toggen {
						width: 50px;
						height: 1em;
						background: none;
						border: none;
						cursor: pointer;
					}

					.bloghash-mobile-toggen .bloghash-icon {
						transform: none;
						width: 50px;
						margin: 0;
						position: absolute;
						right: 0;
						pointer-events: none;
						height: 1em;
					}

					#bloghash-header-inner .site-navigation > ul .menu-item-has-children.bloghash-open > .bloghash-mobile-toggen > .bloghash-icon {
						transform: rotate(180deg);
					}

					' . $header_layout_3_additional_css . '
					' . $header_layout_4_additional_css . '
					' . $header_layout_6_additional_css . '
				}
			';

			/**
			 * Main Navigation.
			 */

			// Font Color.
			$main_nav_font_color = bloghash_option( 'main_nav_font_color' );

			if ( $main_nav_font_color['link-color'] ) {
				$css .= '#bloghash-header-inner .bloghash-nav > ul > li > a { color: ' . bloghash_sanitize_color( $main_nav_font_color['link-color'] ) . '; }';
			}

			if ( $main_nav_font_color['link-hover-color'] ) {
				$css .= '
					#bloghash-header-inner .bloghash-nav > ul > li > a:hover,
					#bloghash-header-inner .bloghash-nav > ul > li.menu-item-has-children:hover > a,
					#bloghash-header-inner .bloghash-nav > ul > li.current-menu-item > a,
					#bloghash-header-inner .bloghash-nav > ul > li.current-menu-ancestor > a,
					#bloghash-header-inner .bloghash-nav > ul > li.page_item_has_children:hover > a,
					#bloghash-header-inner .bloghash-nav > ul > li.current_page_item > a,
					#bloghash-header-inner .bloghash-nav > ul > li.current_page_ancestor > a {
						color: ' . bloghash_sanitize_color( $main_nav_font_color['link-hover-color'] ) . ';
					}
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li > a:hover,
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.menu-item-has-children:hover > a,
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.current-menu-item > a,
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.current-menu-ancestor > a,
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.page_item_has_children:hover > a,
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.current_page_item > a,
					.bloghash-menu-animation-squareborder:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.current_page_ancestor > a {
						color: ' . bloghash_sanitize_color( $main_nav_font_color['link-hover-color'] ) . ';
						border-color: ' . bloghash_sanitize_color( $main_nav_font_color['link-hover-color'] ) . ';
					}
				';
			}

			if ( 'layout-3' === bloghash_option( 'header_layout' ) ) {

				// Background.
				$css .= $this->get_design_options_field_css( '.bloghash-header-layout-3 .bloghash-nav-container', 'main_nav_background', 'background' );

				// Border.
				$css .= $this->get_design_options_field_css( '.bloghash-header-layout-3 .bloghash-nav-container', 'main_nav_border', 'border' );
			} elseif ( 'layout-4' === bloghash_option( 'header_layout' ) ) {

				// Background.
				$css .= $this->get_design_options_field_css( '.bloghash-header-layout-4 .bloghash-nav-container', 'main_nav_background', 'background' );

				// Border.
				$css .= $this->get_design_options_field_css( '.bloghash-header-layout-4 .bloghash-nav-container', 'main_nav_border', 'border' );
			} elseif ( 'layout-6' === bloghash_option( 'header_layout' ) ) {

				// Background.
				$css .= $this->get_design_options_field_css( '.bloghash-header-layout-6 .bloghash-nav-container', 'main_nav_background', 'background' );

				// Border.
				$css .= $this->get_design_options_field_css( '.bloghash-header-layout-6 .bloghash-nav-container', 'main_nav_border', 'border' );
			}

			// Font size.
			$css .= $this->get_typography_field_css( '.bloghash-nav.bloghash-header-element, .bloghash-header-layout-1 .bloghash-header-widgets, .bloghash-header-layout-2 .bloghash-header-widgets', 'main_nav_font' );

			/**
			 * Hero Section.
			 */
			if ( bloghash_option( 'enable_hero' ) ) {
				// Hero height.
				$css .= $this->get_range_field_css( '#hero .bloghash-hero-slider .bloghash-post-item', 'height', 'hero_slider_height' );
			}

			// Footer Background.
			if ( bloghash_option( 'enable_footer' ) || bloghash_option( 'enable_copyright' ) ) {

				// Background.
				$css .= $this->get_design_options_field_css( '#colophon', 'footer_background', 'background' );

				// Footer font color.
				$footer_font_color = bloghash_option( 'footer_text_color' );

				// Footer text color.
				if ( isset( $footer_font_color['text-color'] ) && $footer_font_color['text-color'] ) {
					$css .= '
						#colophon { 
							color: ' . bloghash_sanitize_color( $footer_font_color['text-color'] ) . ';
						}
					';
				}

				// Footer link color.
				if ( isset( $footer_font_color['link-color'] ) && $footer_font_color['link-color'] ) {
					$css .= '
						#colophon a { 
							color: ' . bloghash_sanitize_color( $footer_font_color['link-color'] ) . '; 
						}
					';
				}

				// Footer link hover color.
				if ( isset( $footer_font_color['link-hover-color'] ) && $footer_font_color['link-hover-color'] ) {
					$css .= '
						#colophon a:not(.bloghash-btn):hover,
						.using-keyboard #colophon a:not(.bloghash-btn):focus,
						#colophon li.current_page_item > a,
						#colophon .bloghash-social-nav > ul > li > a .bloghash-icon.bottom-icon { 
							color: ' . bloghash_sanitize_color( $footer_font_color['link-hover-color'] ) . ';
						}
					';
				}

				// Footer widget title.
				if ( isset( $footer_font_color['widget-title-color'] ) && $footer_font_color['widget-title-color'] ) {
					$css .= '
						#colophon .widget-title, #colophon .wp-block-heading, #colophon .wp-block-search__label { 
							color: ' . bloghash_sanitize_color( $footer_font_color['widget-title-color'] ) . ';
						}
					';
				}
			}

			// Main Footer border.
			if ( bloghash_option( 'enable_footer' ) ) {

				// Border.
				$footer_border = bloghash_option( 'footer_border' );

				if ( $footer_border['border-top-width'] ) {
					$css .= '
						#colophon {
							border-top-width: ' . intval( $footer_border['border-top-width'] ) . 'px;
							border-top-style: ' . sanitize_text_field( $footer_border['border-style'] ) . ';
							border-top-color: ' . bloghash_sanitize_color( $footer_border['border-color'] ) . ';
						}
					';
				}

				if ( $footer_border['border-bottom-width'] ) {
					$css .= '
						#colophon {
							border-bottom-width: ' . intval( $footer_border['border-bottom-width'] ) . 'px;
							border-bottom-style: ' . sanitize_text_field( $footer_border['border-style'] ) . ';
							border-bottom-color: ' . bloghash_sanitize_color( $footer_border['border-color'] ) . ';
						}
					';
				}
			}

			// Sidebar.
			$css .= '
				#secondary {
					width: ' . intval( bloghash_option( 'sidebar_width' ) ) . '%;
				}

				body:not(.bloghash-no-sidebar) #primary {
					max-width: ' . intval( 100 - intval( bloghash_option( 'sidebar_width' ) ) ) . '%;
				}
			';

			// Content background.
			$boxed_content_background_color = bloghash_option( 'boxed_content_background_color' );

			// Boxed Separated Layout specific CSS.
			$css .= '
				.bloghash-layout__boxed .bloghash-card-items .bloghash-swiper-buttons,
				.bloghash-card__boxed .bloghash-card-items,
				.bloghash-layout__boxed-separated.author .author-box,
				.bloghash-layout__boxed-separated #comments, 
				.bloghash-layout__boxed-separated #content > article, 
				.bloghash-layout__boxed-separated.bloghash-sidebar-style-2 #secondary .bloghash-widget, 
				.bloghash-layout__boxed-separated.bloghash-sidebar-style-2 .elementor-widget-sidebar .bloghash-widget, 
				.bloghash-layout__boxed-separated.page .bloghash-article,
				.bloghash-layout__boxed-separated.archive .bloghash-article,
				.bloghash-layout__boxed-separated.blog .bloghash-article, 
				.bloghash-layout__boxed-separated.search-results .bloghash-article, 
				.bloghash-layout__boxed-separated.category .bloghash-article {
					background-color: ' . bloghash_sanitize_color( $boxed_content_background_color ) . ';
				}
			';

			$css .= '
				.bloghash-layout__boxed #page {
					background-color: ' . bloghash_sanitize_color( $boxed_content_background_color ) . ';
				}
			';

			// Content text color.
			$content_text_color = bloghash_option( 'content_text_color' );

			$css .= '
				body {
					color: ' . bloghash_sanitize_color( $content_text_color ) . ';
				}

				.comment-form .comment-notes,
				#comments .no-comments,
				#page .wp-caption .wp-caption-text,
				#comments .comment-meta,
				.comments-closed,
				.bloghash-entry cite,
				legend,
				.bloghash-page-header-description,
				.page-links em,
				.site-content .page-links em,
				.single .entry-footer .last-updated,
				.single .post-nav .post-nav-title,
				#main .widget_recent_comments span,
				#main .widget_recent_entries span,
				#main .widget_calendar table > caption,
				.post-thumb-caption,
				.wp-block-image figcaption,
				.wp-block-embed figcaption {
					color: ' . bloghash_sanitize_color( $content_text_color ) . ';
				}
			';

			// bloghash_hex2rgba( $content_text_color, 0.73 )
			// Lightened or darkened background color for backgrounds, borders & inputs.
			$background_color = bloghash_get_background_color();

			$content_text_color_offset = bloghash_light_or_dark( $background_color, bloghash_luminance( $background_color, -0.045 ), bloghash_luminance( $background_color, 0.2 ) );

			// Only add for dark background color.
			if ( ! bloghash_is_light_color( $background_color ) ) {
				$css .= '
					#content textarea,
					#content input[type="text"],
					#content input[type="number"],
					#content input[type="email"],
					#content input[type=password],
					#content input[type=tel],
					#content input[type=url],
					#content input[type=search],
					#content input[type=date] {
						background-color: ' . bloghash_sanitize_color( $background_color ) . ';
					}
				';

				// Offset border color.
				$css .= '
					.bloghash-sidebar-style-2 #secondary .bloghash-widget {
						border-color: ' . bloghash_sanitize_color( $content_text_color_offset ) . ';
					}
				';

				// Offset background color.
				$css .= '
					.entry-meta .entry-meta-elements > span:before {
						background-color: ' . bloghash_sanitize_color( $content_text_color_offset ) . ';
					}
				';
			}

			// Content link hover color.
			$css .= '
				.content-area a:not(.bloghash-btn, .wp-block-button__link, [class^="cat-"], [rel="tag"]):hover,
				#secondary .hester-core-custom-list-widget .bloghash-entry a:not(.bloghash-btn):hover,
				.bloghash-breadcrumbs a:hover {
					color: ' . bloghash_sanitize_color( bloghash_option( 'content_link_hover_color' ) ) . ';
				}
			';

			// Headings Color.
			$css .= '
				h1, h2, h3, h4, h5, h6,
				.h1, .h2, .h3, .h4, a,
				.entry-meta,
				.bloghash-logo .site-title,
				.wp-block-heading,
				.wp-block-search__label,
				.error-404 .page-header h1 {
					color: ' . bloghash_sanitize_color( bloghash_option( 'headings_color' ) ) . ';
				}
				:root {
					--bloghash-secondary: ' . bloghash_sanitize_color( bloghash_option( 'headings_color' ) ) . ';
				}
			';

			// Container width.
			$css .= '
				.bloghash-header-layout-5:not(.bloghash-sticky-header) #bloghash-header #bloghash-header-inner,
				.bloghash-header-layout-5 #masthead+#main .bloghash-breadcrumbs {
					max-width: calc(' . intval( bloghash_option( 'container_width' ) ) . 'px - 8rem);
				}
				.bloghash-container,
				.alignfull.bloghash-wrap-content > div {
					max-width: ' . intval( bloghash_option( 'container_width' ) ) . 'px;
				}

				.bloghash-layout__boxed #page,
				.bloghash-layout__boxed.bloghash-sticky-header.bloghash-is-mobile #bloghash-header-inner,
				.bloghash-layout__boxed.bloghash-sticky-header:not(.bloghash-header-layout-3, .bloghash-header-layout-4, .bloghash-header-layout-6) #bloghash-header-inner,
				.bloghash-layout__boxed.bloghash-sticky-header:not(.bloghash-is-mobile).bloghash-header-layout-6 #bloghash-header-inner .bloghash-nav-container > .bloghash-container,
				.bloghash-layout__boxed.bloghash-sticky-header:not(.bloghash-is-mobile).bloghash-header-layout-4 #bloghash-header-inner .bloghash-nav-container > .bloghash-container,
				.bloghash-layout__boxed.bloghash-sticky-header:not(.bloghash-is-mobile).bloghash-header-layout-3 #bloghash-header-inner .bloghash-nav-container > .bloghash-container {
					max-width: ' . ( intval( bloghash_option( 'container_width' ) ) + 100 ) . 'px;
				}
			';

			// Adjust fullwidth sections for boxed layouts.
			if ( 'boxed' === bloghash_option( 'site_layout' ) || is_customize_preview() ) {
				$css .= '
					@media screen and (max-width: ' . intval( bloghash_option( 'container_width' ) ) . 'px) {
						body.bloghash-layout__boxed.bloghash-no-sidebar .elementor-section.elementor-section-stretched,
						body.bloghash-layout__boxed.bloghash-no-sidebar .bloghash-fw-section,
						body.bloghash-layout__boxed.bloghash-no-sidebar .entry-content .alignfull {
							margin-left: -5rem !important;
							margin-right: -5rem !important;
						}
					}
				';
			}

			// Logo max height.
			$css .= $this->get_range_field_css( '.bloghash-logo img', 'max-height', 'logo_max_height' );
			$css .= $this->get_range_field_css( '.bloghash-logo img.bloghash-svg-logo', 'height', 'logo_max_height' );

			// Logo margin.
			$css .= $this->get_spacing_field_css( '.bloghash-logo .logo-inner', 'margin', 'logo_margin' );

			/**
			 * Transparent header.
			 */

			// Logo max height.
			$css .= $this->get_range_field_css( '.bloghash-tsp-header .bloghash-logo img', 'max-height', 'tsp_logo_max_height' );
			$css .= $this->get_range_field_css( '.bloghash-tsp-header .bloghash-logo img.bloghash-svg-logo', 'height', 'tsp_logo_max_height' );

			// Logo margin.
			$css .= $this->get_spacing_field_css( '.bloghash-tsp-header .bloghash-logo .logo-inner', 'margin', 'tsp_logo_margin' );

			// Main Header custom background.
			$css .= $this->get_design_options_field_css( '.bloghash-tsp-header #bloghash-header-inner', 'tsp_header_background', 'background' );

			/** Font Colors */

			$tsp_font_color = bloghash_option( 'tsp_header_font_color' );

			// Header text color.
			if ( isset( $tsp_font_color['text-color'] ) && $tsp_font_color['text-color'] ) {
				$css .= '
					.bloghash-tsp-header .bloghash-logo .site-description {
						color: ' . bloghash_sanitize_color( $tsp_font_color['text-color'] ) . ';
					}
				';
			}

			// Header link color.
			if ( isset( $tsp_font_color['link-color'] ) && $tsp_font_color['link-color'] ) {
				$css .= '
					.bloghash-tsp-header #bloghash-header,
					.bloghash-tsp-header .bloghash-header-widgets a:not(.bloghash-btn),
					.bloghash-tsp-header .bloghash-logo a,
					.bloghash-tsp-header .bloghash-hamburger,
					.bloghash-tsp-header #bloghash-header-inner .bloghash-nav > ul > li > a { 
						color: ' . bloghash_sanitize_color( $tsp_font_color['link-color'] ) . ';
					}
				';
			}

			// Header link hover color.
			if ( isset( $tsp_font_color['link-hover-color'] ) && $tsp_font_color['link-hover-color'] ) {
				$css .= '
					.bloghash-tsp-header .bloghash-header-widgets a:not(.bloghash-btn):hover, 
					.bloghash-tsp-header #bloghash-header-inner .bloghash-header-widgets .bloghash-active,
					.bloghash-tsp-header .bloghash-logo .site-title a:hover, 
					.bloghash-tsp-header .bloghash-hamburger:hover, 
					.is-mobile-menu-active .bloghash-tsp-header .bloghash-hamburger,
					.bloghash-tsp-header.using-keyboard .site-title a:focus,
					.bloghash-tsp-header.using-keyboard .bloghash-header-widgets a:not(.bloghash-btn):focus,
					.bloghash-tsp-header #bloghash-header-inner .bloghash-nav > ul > li.hovered > a,
					.bloghash-tsp-header #bloghash-header-inner .bloghash-nav > ul > li > a:hover,
					.bloghash-tsp-header #bloghash-header-inner .bloghash-nav > ul > li.menu-item-has-children:hover > a,
					.bloghash-tsp-header #bloghash-header-inner .bloghash-nav > ul > li.current-menu-item > a,
					.bloghash-tsp-header #bloghash-header-inner .bloghash-nav > ul > li.current-menu-ancestor > a,
					.bloghash-tsp-header #bloghash-header-inner .bloghash-nav > ul > li.page_item_has_children:hover > a,
					.bloghash-tsp-header #bloghash-header-inner .bloghash-nav > ul > li.current_page_item > a,
					.bloghash-tsp-header #bloghash-header-inner .bloghash-nav > ul > li.current_page_ancestor > a {
						color: ' . bloghash_sanitize_color( $tsp_font_color['link-hover-color'] ) . ';
					}
					.bloghash-menu-animation-squareborder.bloghash-tsp-header:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li > a:hover,
					.bloghash-menu-animation-squareborder.bloghash-tsp-header:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.menu-item-has-children:hover > a,
					.bloghash-menu-animation-squareborder.bloghash-tsp-header:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.current-menu-item > a,
					.bloghash-menu-animation-squareborder.bloghash-tsp-header:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.current-menu-ancestor > a,
					.bloghash-menu-animation-squareborder.bloghash-tsp-header:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.page_item_has_children:hover > a,
					.bloghash-menu-animation-squareborder.bloghash-tsp-header:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.current_page_item > a,
					.bloghash-menu-animation-squareborder.bloghash-tsp-header:not(.bloghash-is-mobile) #bloghash-header-inner .bloghash-nav > ul > li.current_page_ancestor > a {
						color: ' . bloghash_sanitize_color( $tsp_font_color['link-hover-color'] ) . ';
						border-color: ' . bloghash_sanitize_color( $tsp_font_color['link-hover-color'] ) . ';
					}
				';
			}

			/** Border Color */
			$css .= $this->get_design_options_field_css( '.bloghash-tsp-header #bloghash-header-inner', 'tsp_header_border', 'border' );

			/** Separator Color */
			$css .= $this->get_design_options_field_css( '.bloghash-tsp-header .bloghash-header-widget', 'tsp_header_border', 'separator_color' );

			/**
			 * Page Header.
			 */
			if ( bloghash_option( 'page_header_enable' ) ) {

				// Font size.
				$css .= $this->get_range_field_css( '#page .page-header .page-title', 'font-size', 'page_header_font_size', true );

				// Page Title spacing.
				$css .= $this->get_spacing_field_css( '.bloghash-page-title-align-left .page-header.bloghash-has-page-title, .bloghash-page-title-align-right .page-header.bloghash-has-page-title, .bloghash-page-title-align-center .page-header .bloghash-page-header-wrapper', 'padding', 'page_header_spacing' );

				// Page Header background.
				$css .= $this->get_design_options_field_css( '.bloghash-tsp-header:not(.bloghash-tsp-absolute) #masthead', 'page_header_background', 'background' );
				$css .= $this->get_design_options_field_css( '.page-header', 'page_header_background', 'background' );

				// Page Header font color.
				$page_header_color = bloghash_option( 'page_header_text_color' );

				// Page Header text color.
				if ( isset( $page_header_color['text-color'] ) && $page_header_color['text-color'] ) {
					$css .= '
						.page-header .page-title { 
							color: ' . bloghash_sanitize_color( $page_header_color['text-color'] ) . '; }

						.page-header .bloghash-page-header-description {
							color: ' . bloghash_sanitize_color( bloghash_hex2rgba( $page_header_color['text-color'], 0.75 ) ) . '; 
						}
					';
				}

				// Page Header link color.
				if ( isset( $page_header_color['link-color'] ) && $page_header_color['link-color'] ) {
					$css .= '
						.page-header .bloghash-breadcrumbs a { 
							color: ' . bloghash_sanitize_color( $page_header_color['link-color'] ) . '; }

						.page-header .bloghash-breadcrumbs span,
						.page-header .breadcrumb-trail .trail-items li::after, .page-header .bloghash-breadcrumbs .separator {
							color: ' . bloghash_sanitize_color( bloghash_hex2rgba( $page_header_color['link-color'], 0.75 ) ) . '; 
						}
					';
				}

				// Page Header link hover color.
				if ( isset( $page_header_color['link-hover-color'] ) && $page_header_color['link-hover-color'] ) {
					$css .= '
						.page-header .bloghash-breadcrumbs a:hover { 
							color: ' . bloghash_sanitize_color( $page_header_color['link-hover-color'] ) . '; }
					';
				}

				// Page Header border color.
				$page_header_border = bloghash_option( 'page_header_border' );

				$css .= $this->get_design_options_field_css( '.page-header', 'page_header_border', 'border' );
			}

			/**
			 * Breadcrumbs.
			 */
			if ( bloghash_option( 'breadcrumbs_enable' ) ) {

				// Spacing.
				$css .= $this->get_spacing_field_css( '.bloghash-breadcrumbs', 'padding', 'breadcrumbs_spacing' );

				if ( 'below-header' === bloghash_option( 'breadcrumbs_position' ) ) {

					// Background.
					$css .= $this->get_design_options_field_css( '.bloghash-breadcrumbs', 'breadcrumbs_background', 'background' );

					// Border.
					$css .= $this->get_design_options_field_css( '.bloghash-breadcrumbs', 'breadcrumbs_border', 'border' );

					// Text Color.
					$css .= $this->get_design_options_field_css( '.bloghash-breadcrumbs', 'breadcrumbs_text_color', 'color' );
				}
			}

			/**
			 * Copyright Bar.
			 */
			if ( bloghash_option( 'enable_copyright' ) ) {
				$css .= $this->get_design_options_field_css( '#bloghash-copyright', 'copyright_background', 'background' );

				// Copyright font color.
				$copyright_color = bloghash_option( 'copyright_text_color' );

				// Copyright text color.
				if ( isset( $copyright_color['text-color'] ) && $copyright_color['text-color'] ) {
					$css .= '
						#bloghash-copyright { 
							color: ' . bloghash_sanitize_color( $copyright_color['text-color'] ) . '; }
					';
				}

				// Copyright link color.
				if ( isset( $copyright_color['link-color'] ) && $copyright_color['link-color'] ) {
					$css .= '
						#bloghash-copyright a { 
							color: ' . bloghash_sanitize_color( $copyright_color['link-color'] ) . '; }
					';
				}

				// Copyright link hover color.
				if ( isset( $copyright_color['link-hover-color'] ) && $copyright_color['link-hover-color'] ) {
					$css .= '
						#bloghash-copyright a:hover,
						.using-keyboard #bloghash-copyright a:focus,
						#bloghash-copyright .bloghash-social-nav > ul > li > a .bloghash-icon.bottom-icon,
						#bloghash-copyright .bloghash-nav > ul > li.current-menu-item > a,
						#bloghash-copyright .bloghash-nav > ul > li.current-menu-ancestor > a,
						#bloghash-copyright .bloghash-nav > ul > li:hover > a { 
							color: ' . bloghash_sanitize_color( $copyright_color['link-hover-color'] ) . '; }
					';
				}

				// Copyright separator color.
				$footer_text_color = bloghash_option( 'footer_text_color' );
				$footer_text_color = $footer_text_color['text-color'];

				$copyright_separator_color = bloghash_light_or_dark( $footer_text_color, 'rgba(255,255,255,0.1)', 'rgba(0,0,0,0.1)' );

				$css .= '
					#bloghash-copyright.contained-separator > .bloghash-container::before {
						background-color: ' . bloghash_sanitize_color( $copyright_separator_color ) . ';
					}

					#bloghash-copyright.fw-separator {
						border-top-color: ' . bloghash_sanitize_color( $copyright_separator_color ) . ';
					}
				';
			}

			/**
			 * Typography.
			 */

			// Base HTML font size.
			$css .= $this->get_range_field_css( 'html', 'font-size', 'html_base_font_size', true, '%' );

			// Font smoothing.
			if ( bloghash_option( 'font_smoothing' ) ) {
				$css .= '
					* {
						-moz-osx-font-smoothing: grayscale;
						-webkit-font-smoothing: antialiased;
					}
				';
			}

			// Body.
			$css .= $this->get_typography_field_css( 'body', 'body_font' );

			// Headings.
			$css .= $this->get_typography_field_css( 'h1, .h1, .bloghash-logo .site-title, .page-header .page-title, h2, .h2, h3, .h3, h4, .h4, h5, .h5, h6, .h6', 'headings_font' );

			$css .= $this->get_typography_field_css( 'h1, .h1, .bloghash-logo .site-title, .page-header .page-title', 'h1_font' );
			$css .= $this->get_typography_field_css( 'h2, .h2', 'h2_font' );
			$css .= $this->get_typography_field_css( 'h3, .h3', 'h3_font' );
			$css .= $this->get_typography_field_css( 'h4, .h4', 'h4_font' );
			$css .= $this->get_typography_field_css( 'h5, .h5', 'h5_font' );
			$css .= $this->get_typography_field_css( 'h6, .h6', 'h6_font' );
			$css .= $this->get_typography_field_css( 'h1 em, h2 em, h3 em, h4 em, h5 em, h6 em, .h1 em, .h2 em, .h3 em, .h4 em, .h5 em, .h6 em, .bloghash-logo .site-title em, .error-404 .page-header h1 em', 'heading_em_font' );

			// Emphasized Heading.
			$css .= $this->get_typography_field_css( 'h1 em, h2 em, h3 em, h4 em, h5 em, h6 em, .h1 em, .h2 em, .h3 em, .h4 em, .h5 em, .h6 em, .bloghash-logo .site-title em, .error-404 .page-header h1 em', 'heading_em_font' );

			// Site Title font size.
			$css .= $this->get_range_field_css( '#bloghash-header .bloghash-logo .site-title', 'font-size', 'logo_text_font_size', true );

			// Hero post title font size.
			$css .= $this->get_range_field_css( '#hero .entry-header .entry-title', 'font-size', 'hero_slider_title_font_size', true );

			// PYML post title font size.
			$css .= $this->get_range_field_css( '#pyml .entry-header .entry-title', 'font-size', 'pyml_post_title_font_size', true );

			// Sidebar widget title.
			$css .= $this->get_range_field_css( '#main .widget-title, #secondary .bloghash-widget .wp-block-heading, #secondary .bloghash-widget .wp-block-search__label', 'font-size', 'sidebar_widget_title_font_size', true );

			// Footer widget title.
			$css .= $this->get_range_field_css( '#colophon .widget-title, #colophon .wp-block-heading', 'font-size', 'footer_widget_title_font_size', true );

			// Blog Single Post - Title Spacing.
			$css .= $this->get_spacing_field_css( '.bloghash-single-title-in-page-header #page .page-header .bloghash-page-header-wrapper', 'padding', 'single_title_spacing', true );

			// Blog Single Post - Content Font Size.
			$css .= $this->get_range_field_css( '.single-post .entry-content', 'font-size', 'single_content_font_size', true );

			// Blog Single Post - narrow container.
			if ( 'narrow' === bloghash_option( 'single_content_width' ) ) {
				$css .= '
					.single-post.narrow-content .entry-content > :not([class*="align"]):not([class*="gallery"]):not(.wp-block-image):not(.quote-inner):not(.quote-post-bg), 
					.single-post.narrow-content .mce-content-body:not([class*="page-template-full-width"]) > :not([class*="align"]):not([data-wpview-type*="gallery"]):not(blockquote):not(.mceTemp), 
					.single-post.narrow-content .entry-footer, 
					.single-post.narrow-content .entry-content > .alignwide,
					.single-post.narrow-content p.has-background:not(.alignfull):not(.alignwide),
					.single-post.narrow-content .post-nav, 
					.single-post.narrow-content #bloghash-comments-toggle, 
					.single-post.narrow-content #comments, 
					.single-post.narrow-content .entry-content .aligncenter, .single-post.narrow-content .bloghash-narrow-element, 
					.single-post.narrow-content.bloghash-single-title-in-content .entry-header, 
					.single-post.narrow-content.bloghash-single-title-in-content .entry-meta, 
					.single-post.narrow-content.bloghash-single-title-in-content .post-category,
					.single-post.narrow-content.bloghash-no-sidebar .bloghash-page-header-wrapper,
					.single-post.narrow-content.bloghash-no-sidebar .bloghash-breadcrumbs nav {
						max-width: ' . intval( bloghash_option( 'single_narrow_container_width' ) ) . 'px;
						margin-left: auto;
						margin-right: auto;
					}

					.single-post.narrow-content .author-box,
					.single-post.narrow-content .entry-content > .alignwide,
					.single.bloghash-single-title-in-page-header .page-header.bloghash-align-center .bloghash-page-header-wrapper {
						max-width: ' . ( intval( bloghash_option( 'single_narrow_container_width' ) ) + 70 ) . 'px;
					}
				';
			}

			// Allow CSS to be filtered.
			$css = apply_filters( 'bloghash_dynamic_styles', $css );

			// Add user custom CSS.
			if ( $custom_css || ! is_customize_preview() ) {
				$css .= wp_get_custom_css();
			}

			// Minify the CSS code.
			$css = $this->minify( $css );

			return $css;
		}

		/**
		 * Update dynamic css file with new CSS. Cleans caches after that.
		 *
		 * @return [Boolean] returns true if successfully updated the dynamic file.
		 */
		public function update_dynamic_file() {

			$css = $this->get_css( true );

			if ( empty( $css ) || '' === trim( $css ) ) {
				return;
			}

			// Load file.php file.
			require_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'file.php'; // phpcs:ignore

			global $wp_filesystem;

			// Check if the the global filesystem isn't setup yet.
			if ( is_null( $wp_filesystem ) ) {
				WP_Filesystem();
			}

			$wp_filesystem->mkdir( $this->dynamic_css_path );

			if ( $wp_filesystem->put_contents( $this->dynamic_css_path . 'dynamic-styles.css', $css ) ) {
				$this->clean_cache();
				set_transient( 'bloghash_has_dynamic_css', true, 0 );
				return true;
			}

			return false;
		}

		/**
		 * Delete dynamic css file.
		 *
		 * @return void
		 */
		public function delete_dynamic_file() {

			// Load file.php file.
			require_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'file.php'; // phpcs:ignore

			global $wp_filesystem;

			// Check if the the global filesystem isn't setup yet.
			if ( is_null( $wp_filesystem ) ) {
				WP_Filesystem();
			}

			$wp_filesystem->delete( $this->dynamic_css_path . 'dynamic-styles.css' );

			delete_transient( 'bloghash_has_dynamic_css' );
		}

		/**
		 * Simple CSS code minification.
		 *
		 * @param  string $css code to be minified.
		 * @return string, minifed code
		 * @since  1.0.0
		 */
		private function minify( $css ) {
			$css = preg_replace( '/\s+/', ' ', $css );
			$css = preg_replace( '/\/\*[^\!](.*?)\*\//', '', $css );
			$css = preg_replace( '/(,|:|;|\{|}) /', '$1', $css );
			$css = preg_replace( '/ (,|;|\{|})/', '$1', $css );
			$css = preg_replace( '/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css );
			$css = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css );

			return trim( $css );
		}

		/**
		 * Cleans various caches. Compatible with cache plugins.
		 *
		 * @since 1.0.0
		 */
		private function clean_cache() {

			// If W3 Total Cache is being used, clear the cache.
			if ( function_exists( 'w3tc_pgcache_flush' ) ) {
				w3tc_pgcache_flush();
			}

			// if WP Super Cache is being used, clear the cache.
			if ( function_exists( 'wp_cache_clean_cache' ) ) {
				global $file_prefix;
				wp_cache_clean_cache( $file_prefix );
			}

			// If SG CachePress is installed, reset its caches.
			if ( class_exists( 'SG_CachePress_Supercacher' ) ) {
				if ( method_exists( 'SG_CachePress_Supercacher', 'purge_cache' ) ) {
					SG_CachePress_Supercacher::purge_cache();
				}
			}

			// Clear caches on WPEngine-hosted sites.
			if ( class_exists( 'WpeCommon' ) ) {

				if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) {
					WpeCommon::purge_memcached();
				}

				if ( method_exists( 'WpeCommon', 'clear_maxcdn_cache' ) ) {
					WpeCommon::clear_maxcdn_cache();
				}

				if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {
					WpeCommon::purge_varnish_cache();
				}
			}

			// Clean OpCache.
			if ( function_exists( 'opcache_reset' ) ) {
				opcache_reset(); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.opcache_resetFound
			}

			// Clean WordPress cache.
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}
		}

		/**
		 * Prints spacing field CSS based on passed params.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $css_selector CSS selector.
		 * @param  string $css_property CSS property, such as 'margin', 'padding' or 'border'.
		 * @param  string $setting_id The ID of the customizer setting containing all information about the setting.
		 * @param  bool   $responsive Has responsive values.
		 * @return string  Generated CSS.
		 */
		public function get_spacing_field_css( $css_selector, $css_property, $setting_id, $responsive = true ) {

			// Get the saved setting.
			$setting = bloghash_option( $setting_id );

			// If setting doesn't exist, return.
			if ( ! is_array( $setting ) ) {
				return;
			}

			// Get the unit. Defaults to px.
			$unit = 'px';

			if ( isset( $setting['unit'] ) ) {
				if ( $setting['unit'] ) {
					$unit = $setting['unit'];
				}

				unset( $setting['unit'] );
			}

			// CSS buffer.
			$css_buffer = '';

			// Loop through options.
			foreach ( $setting as $key => $value ) {

				// Check if responsive options are available.
				if ( is_array( $value ) ) {

					if ( 'desktop' === $key ) {
						$mq_open  = '';
						$mq_close = '';
					} elseif ( 'tablet' === $key ) {
						$mq_open  = '@media only screen and (max-width: 768px) {';
						$mq_close = '}';
					} elseif ( 'mobile' === $key ) {
						$mq_open  = '@media only screen and (max-width: 480px) {';
						$mq_close = '}';
					} else {
						$mq_open  = '';
						$mq_close = '';
					}

					// Add media query prefix.
					$css_buffer .= $mq_open . $css_selector . '{';

					// Loop through all choices.
					foreach ( $value as $pos => $val ) {

						if ( empty( $val ) ) {
							continue;
						}

						if ( 'border' === $css_property ) {
							$pos .= '-width';
						}

						$css_buffer .= $css_property . '-' . $pos . ': ' . intval( $val ) . $unit . ';';
					}

					$css_buffer .= '}' . $mq_close;
				} else {

					if ( 'border' === $css_property ) {
						$key .= '-width';
					}

					$css_buffer .= $css_property . '-' . $key . ': ' . intval( $value ) . $unit . ';';
				}
			}

			// Check if field is has responsive values.
			if ( ! $responsive ) {
				$css_buffer = $css_selector . '{' . $css_buffer . '}';
			}

			// Finally, return the generated CSS code.
			return $css_buffer;
		}

		/**
		 * Prints range field CSS based on passed params.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $css_selector CSS selector.
		 * @param  string $css_property CSS property, such as 'margin', 'padding' or 'border'.
		 * @param  string $setting_id The ID of the customizer setting containing all information about the setting.
		 * @param  bool   $responsive Has responsive values.
		 * @param  string $unit Unit.
		 * @return string  Generated CSS.
		 */
		public function get_range_field_css( $css_selector, $css_property, $setting_id, $responsive = true, $unit = 'px' ) {

			// Get the saved setting.
			$setting = bloghash_option( $setting_id );

			// If just a single value option.
			if ( ! is_array( $setting ) ) {
				return $css_selector . ' { ' . $css_property . ': ' . $setting . $unit . '; }';
			}

			// Resolve units.
			if ( isset( $setting['unit'] ) ) {
				if ( $setting['unit'] ) {
					$unit = $setting['unit'];
				}

				unset( $setting['unit'] );
			}

			// CSS buffer.
			$css_buffer = '';

			if ( is_array( $setting ) && ! empty( $setting ) ) {

				// Media query syntax wrap.
				$mq_open  = '';
				$mq_close = '';

				// Loop through options.
				foreach ( $setting as $key => $value ) {

					if ( empty( $value ) ) {
						continue;
					}

					if ( 'desktop' === $key ) {
						$mq_open  = '';
						$mq_close = '';
					} elseif ( 'tablet' === $key ) {
						$mq_open  = '@media only screen and (max-width: 768px) {';
						$mq_close = '}';
					} elseif ( 'mobile' === $key ) {
						$mq_open  = '@media only screen and (max-width: 480px) {';
						$mq_close = '}';
					} else {
						$mq_open  = '';
						$mq_close = '';
					}

					// Add media query prefix.
					$css_buffer .= $mq_open . $css_selector . '{';
					$css_buffer .= $css_property . ': ' . floatval( $value ) . $unit . ';';
					$css_buffer .= '}' . $mq_close;
				}
			}

			// Finally, return the generated CSS code.
			return $css_buffer;
		}

		/**
		 * Prints design options field CSS based on passed params.
		 *
		 * @since 1.0.0
		 * @param string       $css_selector CSS selector.
		 * @param string|mixed $setting The ID of the customizer setting containing all information about the setting.
		 * @param string       $type Design options field type.
		 * @return string      Generated CSS.
		 */
		/**
		 * Prints design options field CSS based on passed params.
		 *
		 * @since 1.0.0
		 * @param string       $css_selector CSS selector.
		 * @param string|mixed $setting The ID of the customizer setting containing all information about the setting.
		 * @param string       $type Design options field type.
		 * @return string      Generated CSS.
		 */
		public function get_design_options_field_css( $css_selector, $setting, $type ) {

			if ( is_string( $setting ) ) {
				// Get the saved setting.
				$setting = bloghash_option( $setting );
			}

			// Setting has to be array.
			if ( ! is_array( $setting ) || empty( $setting ) ) {
				return;
			}

			// CSS buffer.
			$css_buffer = '';

			// Background.
			if ( 'background' === $type ) {

				// Background type.
				$background_type = $setting['background-type'];

				if ( 'color' === $background_type ) {
					if ( isset( $setting['background-color'] ) && ! empty( $setting['background-color'] ) ) {
						$css_buffer .= 'background: ' . bloghash_sanitize_color( $setting['background-color'] ) . ';';
					}
				} elseif ( 'gradient' === $background_type ) {

					$css_buffer .= 'background: ' . bloghash_sanitize_color( $setting['gradient-color-1'] ) . ';';

					if ( 'linear' === $setting['gradient-type'] ) {
						$css_buffer .= '
							background: -webkit-linear-gradient(' . intval( $setting['gradient-linear-angle'] ) . 'deg, ' . bloghash_sanitize_color( $setting['gradient-color-1'] ) . ' ' . intval( $setting['gradient-color-1-location'] ) . '%, ' . bloghash_sanitize_color( $setting['gradient-color-2'] ) . ' ' . intval( $setting['gradient-color-2-location'] ) . '%);
							background: -o-linear-gradient(' . intval( $setting['gradient-linear-angle'] ) . 'deg, ' . bloghash_sanitize_color( $setting['gradient-color-1'] ) . ' ' . intval( $setting['gradient-color-1-location'] ) . '%, ' . bloghash_sanitize_color( $setting['gradient-color-2'] ) . ' ' . intval( $setting['gradient-color-2-location'] ) . '%);
							background: linear-gradient(' . intval( $setting['gradient-linear-angle'] ) . 'deg, ' . bloghash_sanitize_color( $setting['gradient-color-1'] ) . ' ' . intval( $setting['gradient-color-1-location'] ) . '%, ' . bloghash_sanitize_color( $setting['gradient-color-2'] ) . ' ' . intval( $setting['gradient-color-2-location'] ) . '%);

						';
					} elseif ( 'radial' === $setting['gradient-type'] ) {
						$css_buffer .= '
							background: -webkit-radial-gradient(' . sanitize_text_field( $setting['gradient-position'] ) . ', circle, ' . bloghash_sanitize_color( $setting['gradient-color-1'] ) . ' ' . intval( $setting['gradient-color-1-location'] ) . '%, ' . bloghash_sanitize_color( $setting['gradient-color-2'] ) . ' ' . intval( $setting['gradient-color-2-location'] ) . '%);
							background: -o-radial-gradient(' . sanitize_text_field( $setting['gradient-position'] ) . ', circle, ' . bloghash_sanitize_color( $setting['gradient-color-1'] ) . ' ' . intval( $setting['gradient-color-1-location'] ) . '%, ' . bloghash_sanitize_color( $setting['gradient-color-2'] ) . ' ' . intval( $setting['gradient-color-2-location'] ) . '%);
							background: radial-gradient(circle at ' . sanitize_text_field( $setting['gradient-position'] ) . ', ' . bloghash_sanitize_color( $setting['gradient-color-1'] ) . ' ' . intval( $setting['gradient-color-1-location'] ) . '%, ' . bloghash_sanitize_color( $setting['gradient-color-2'] ) . ' ' . intval( $setting['gradient-color-2-location'] ) . '%);
						';
					}
				} elseif ( 'image' === $background_type ) {
					$css_buffer .= '
						background-image: url(' . esc_url( $setting['background-image'] ) . ');
						background-size: ' . sanitize_text_field( $setting['background-size'] ) . ';
						background-attachment: ' . sanitize_text_field( $setting['background-attachment'] ) . ';
						background-position: ' . intval( $setting['background-position-x'] ) . '% ' . intval( $setting['background-position-y'] ) . '%;
						background-repeat: ' . sanitize_text_field( $setting['background-repeat'] ) . ';
					';
				}

				$css_buffer = ! empty( $css_buffer ) ? $css_selector . '{' . $css_buffer . '}' : '';

				if ( 'image' === $background_type && isset( $setting['background-color-overlay'] ) && $setting['background-color-overlay'] && isset( $setting['background-image'] ) && $setting['background-image'] ) {
					$css_buffer .= $css_selector . '::after { background-color: ' . bloghash_sanitize_color( $setting['background-color-overlay'] ) . '; }';
				}
			} elseif ( 'color' === $type ) {

				// Text color.
				if ( isset( $setting['text-color'] ) && ! empty( $setting['text-color'] ) ) {
					$css_buffer .= $css_selector . ' { color: ' . bloghash_sanitize_color( $setting['text-color'] ) . '; }';
				}

				// Link Color.
				if ( isset( $setting['link-color'] ) && ! empty( $setting['link-color'] ) ) {
					$css_buffer .= $css_selector . ' a { color: ' . bloghash_sanitize_color( $setting['link-color'] ) . '; }';
				}

				// Link Hover Color.
				if ( isset( $setting['link-hover-color'] ) && ! empty( $setting['link-hover-color'] ) ) {
					$css_buffer .= $css_selector . ' a:hover { color: ' . bloghash_sanitize_color( $setting['link-hover-color'] ) . ' !important; }';
				}
			} elseif ( 'border' === $type ) {

				// Color.
				if ( isset( $setting['border-color'] ) && ! empty( $setting['border-color'] ) ) {
					$css_buffer .= 'border-color:' . bloghash_sanitize_color( $setting['border-color'] ) . ';';
				}

				// Style.
				if ( isset( $setting['border-style'] ) && ! empty( $setting['border-style'] ) ) {
					$css_buffer .= 'border-style: ' . sanitize_text_field( $setting['border-style'] ) . ';';
				}

				// Width.
				$positions = array( 'top', 'right', 'bottom', 'left' );

				foreach ( $positions as $position ) {
					if ( isset( $setting[ 'border-' . $position . '-width' ] ) && ! empty( $setting[ 'border-' . $position . '-width' ] ) ) {
						$css_buffer .= 'border-' . sanitize_text_field( $position ) . '-width: ' . $setting[ 'border-' . sanitize_text_field( $position ) . '-width' ] . 'px;';
					}
				}

				$css_buffer = ! empty( $css_buffer ) ? $css_selector . '{' . $css_buffer . '}' : '';
			} elseif ( 'separator_color' === $type && isset( $setting['separator-color'] ) && ! empty( $setting['separator-color'] ) ) {

				// Separator Color.
				$css_buffer .= $css_selector . '::after { background-color:' . bloghash_sanitize_color( $setting['separator-color'] ) . '; }';
			}

			// Finally, return the generated CSS code.
			return $css_buffer;
		}

		/**
		 * Prints typography field CSS based on passed params.
		 *
		 * @since  1.0.0
		 * @param  string       $css_selector CSS selector.
		 * @param  string|mixed $setting The ID of the customizer setting containing all information about the setting.
		 * @return string       Generated CSS.
		 */
		public function get_typography_field_css( $css_selector, $setting ) {

			if ( is_string( $setting ) ) {
				// Get the saved setting.
				$setting = bloghash_option( $setting );
			}

			// Setting has to be array.
			if ( ! is_array( $setting ) || empty( $setting ) ) {
				return;
			}

			// CSS buffer.
			$css_buffer = '';

			// Properties.
			$properties = array(
				'font-weight',
				'font-style',
				'text-transform',
				'text-decoration',
			);

			foreach ( $properties as $property ) {

				if ( 'inherit' !== $setting[ $property ] ) {
					$css_buffer .= $property . ':' . $setting[ $property ] . ';';
				}
			}

			// Font family.
			if ( 'inherit' !== $setting['font-family'] ) {
				$font_family = bloghash()->fonts->get_font_family( $setting['font-family'] );

				$css_buffer .= 'font-family: ' . sanitize_text_field( $font_family ) . ';';
			}

			// Letter spacing.
			if ( ! empty( $setting['letter-spacing'] ) ) {
				$css_buffer .= 'letter-spacing:' . floatval( $setting['letter-spacing'] ) . sanitize_text_field( $setting['letter-spacing-unit'] ) . ';';
			}

			// Font size.
			if ( ! empty( $setting['font-size-desktop'] ) ) {
				$css_buffer .= 'font-size:' . floatval( $setting['font-size-desktop'] ) . sanitize_text_field( $setting['font-size-unit'] ) . ';';
			}

			// Line Height.
			if ( ! empty( $setting['line-height-desktop'] ) ) {
				$css_buffer .= 'line-height:' . floatval( $setting['line-height-desktop'] ) . ';';
			}

			$css_buffer = $css_buffer ? $css_selector . '{' . $css_buffer . '}' : '';

			// Responsive options - tablet.
			$tablet = '';

			if ( ! empty( $setting['font-size-tablet'] ) ) {
				$tablet .= 'font-size:' . floatval( $setting['font-size-tablet'] ) . sanitize_text_field( $setting['font-size-unit'] ) . ';';
			}

			if ( ! empty( $setting['line-height-tablet'] ) ) {
				$tablet .= 'line-height:' . floatval( $setting['line-height-tablet'] ) . ';';
			}

			$tablet = ! empty( $tablet ) ? '@media only screen and (max-width: 768px) {' . $css_selector . '{' . $tablet . '} }' : '';

			$css_buffer .= $tablet;

			// Responsive options - mobile.
			$mobile = '';

			if ( ! empty( $setting['font-size-mobile'] ) ) {
				$mobile .= 'font-size:' . floatval( $setting['font-size-mobile'] ) . sanitize_text_field( $setting['font-size-unit'] ) . ';';
			}

			if ( ! empty( $setting['line-height-mobile'] ) ) {
				$mobile .= 'line-height:' . floatval( $setting['line-height-mobile'] ) . ';';
			}

			$mobile = ! empty( $mobile ) ? '@media only screen and (max-width: 480px) {' . $css_selector . '{' . $mobile . '} }' : '';

			$css_buffer .= $mobile;

			// Equeue google fonts.
			if ( bloghash()->fonts->is_google_font( $setting['font-family'] ) ) {

				$params = array();

				if ( 'inherit' !== $setting['font-weight'] ) {
					$params['weight'] = $setting['font-weight'];
				}

				if ( 'inherit' !== $setting['font-style'] ) {
					$params['style'] = $setting['font-style'];
				}

				if ( $setting['font-subsets'] && ! empty( $setting['font-subsets'] ) ) {
					$params['subsets'] = $setting['font-subsets'];
				}

				bloghash()->fonts->enqueue_google_font(
					$setting['font-family'],
					$params
				);
			}

			// Finally, return the generated CSS code.
			return $css_buffer;
		}

		/**
		 * Filters the dynamic styles to include button styles and makes sure it has the highest priority.
		 *
		 * @since  1.0.0
		 * @param  string $css The dynamic CSS.
		 * @return string Filtered dynamic CSS.
		 */
		public function get_button_styles( $css ) {

			/**
			 * Primary Button.
			 */

			$primary_button_selector = '
				.bloghash-btn,
				body:not(.wp-customizer) input[type=submit], 
				.site-main .woocommerce #respond input#submit, 
				.site-main .woocommerce a.button, 
				.site-main .woocommerce button.button, 
				.site-main .woocommerce input.button, 
				.woocommerce ul.products li.product .added_to_cart, 
				.woocommerce ul.products li.product .button, 
				.woocommerce div.product form.cart .button, 
				.woocommerce #review_form #respond .form-submit input, 
				#infinite-handle span';

			$primary_button_bg_color      = bloghash_option( 'primary_button_bg_color' );
			$primary_button_border_radius = bloghash_option( 'primary_button_border_radius' );

			if ( '' !== $primary_button_bg_color ) {
				$css .= $primary_button_selector . ' {
					background-color: ' . bloghash_sanitize_color( $primary_button_bg_color ) . ';
				}';
			}

			// Primary button text color, border color & border width.
			$css .= $primary_button_selector . ' {
				color: ' . bloghash_sanitize_color( bloghash_option( 'primary_button_text_color' ) ) . ';
				border-color: ' . bloghash_sanitize_color( bloghash_option( 'primary_button_border_color' ) ) . ';
				border-width: ' . floatval( bloghash_option( 'primary_button_border_width' ) ) . 'rem;
				border-top-left-radius: ' . floatval( $primary_button_border_radius['top-left'] ) . 'rem;
				border-top-right-radius: ' . floatval( $primary_button_border_radius['top-right'] ) . 'rem;
				border-bottom-right-radius: ' . floatval( $primary_button_border_radius['bottom-right'] ) . 'rem;
				border-bottom-left-radius: ' . floatval( $primary_button_border_radius['bottom-left'] ) . 'rem;
			}';

			// Primary button hover.
			$primary_button_hover_selector = '
				.bloghash-btn:hover, 
				.bloghash-btn:focus, 
				body:not(.wp-customizer) input[type=submit]:hover,
				body:not(.wp-customizer) input[type=submit]:focus, 
				.site-main .woocommerce #respond input#submit:hover,
				.site-main .woocommerce #respond input#submit:focus, 
				.site-main .woocommerce a.button:hover,
				.site-main .woocommerce a.button:focus, 
				.site-main .woocommerce button.button:hover,
				.site-main .woocommerce button.button:focus, 
				.site-main .woocommerce input.button:hover, 
				.site-main .woocommerce input.button:focus, 
				.woocommerce ul.products li.product .added_to_cart:hover,
				.woocommerce ul.products li.product .added_to_cart:focus, 
				.woocommerce ul.products li.product .button:hover,
				.woocommerce ul.products li.product .button:focus, 
				.woocommerce div.product form.cart .button:hover,
				.woocommerce div.product form.cart .button:focus, 
				.woocommerce #review_form #respond .form-submit input:hover,
				.woocommerce #review_form #respond .form-submit input:focus, 
				#infinite-handle span:hover';

			$primary_button_hover_bg_color = bloghash_option( 'primary_button_hover_bg_color' );

			// Primary button hover bg color.
			if ( '' !== $primary_button_hover_bg_color ) {
				$css .= $primary_button_hover_selector . ' {
					background-color: ' . bloghash_sanitize_color( $primary_button_hover_bg_color ) . ';
				}';
			}

			// Primary button hover color & border.
			$css .= $primary_button_hover_selector . '{
				color: ' . bloghash_sanitize_color( bloghash_option( 'primary_button_hover_text_color' ) ) . ';
				border-color: ' . bloghash_sanitize_color( bloghash_option( 'primary_button_hover_border_color' ) ) . ';
			}';

			// Primary button typography.
			$css .= $this->get_typography_field_css( $primary_button_selector, 'primary_button_typography' );

			/**
			 * Secondary Button.
			 */

			$secondary_button_selector = '
				.btn-secondary,
				.bloghash-btn.btn-secondary';

			$secondary_button_bg_color      = bloghash_option( 'secondary_button_bg_color' );
			$secondary_button_border_radius = bloghash_option( 'secondary_button_border_radius' );

			// Secondary button text color, border color & border width.
			$css .= $secondary_button_selector . ' {
				color: ' . bloghash_sanitize_color( bloghash_option( 'secondary_button_text_color' ) ) . ';
				border-color: ' . bloghash_sanitize_color( bloghash_option( 'secondary_button_border_color' ) ) . ';
				border-width: ' . floatval( bloghash_option( 'secondary_button_border_width' ) ) . 'rem;
				background-color: ' . bloghash_sanitize_color( $secondary_button_bg_color ) . ';
				border-top-left-radius: ' . floatval( $secondary_button_border_radius['top-left'] ) . 'rem;
				border-top-right-radius: ' . floatval( $secondary_button_border_radius['top-right'] ) . 'rem;
				border-bottom-right-radius: ' . floatval( $secondary_button_border_radius['bottom-right'] ) . 'rem;
				border-bottom-left-radius: ' . floatval( $secondary_button_border_radius['bottom-left'] ) . 'rem;
			}';

			// Secondary button hover.
			$secondary_button_hover_selector = '
				.btn-secondary:hover, 
				.btn-secondary:focus, 
				.bloghash-btn.btn-secondary:hover, 
				.bloghash-btn.btn-secondary:focus';

			$secondary_button_hover_bg_color = bloghash_option( 'secondary_button_hover_bg_color' );

			// Secondary button hover color & border.
			$css .= $secondary_button_hover_selector . '{
				color: ' . bloghash_sanitize_color( bloghash_option( 'secondary_button_hover_text_color' ) ) . ';
				border-color: ' . bloghash_sanitize_color( bloghash_option( 'secondary_button_hover_border_color' ) ) . ';
				background-color: ' . bloghash_sanitize_color( $secondary_button_hover_bg_color ) . ';
			}';

			// Secondary button typography.
			$css .= $this->get_typography_field_css( $secondary_button_selector, 'secondary_button_typography' );

			// Text Button.
			$css .= '
				.bloghash-btn.btn-text-1, .btn-text-1 {
					color: ' . bloghash_sanitize_color( bloghash_option( 'text_button_text_color' ) ) . ';
				}
			';

			$css .= '
				.bloghash-btn.btn-text-1:hover, .bloghash-btn.btn-text-1:focus, .btn-text-1:hover, .btn-text-1:focus {
					color: ' . bloghash_sanitize_color( bloghash_option( 'accent_color' ) ) . ';
				}
			';

			$css .= '
				.bloghash-btn.btn-text-1 > span::before {
					background-color: ' . bloghash_sanitize_color( bloghash_option( 'accent_color' ) ) . ';
				}
			';

			if ( bloghash_option( 'text_button_hover_text_color' ) ) {
				$css .= '
					.bloghash-btn.btn-text-1:hover, .bloghash-btn.btn-text-1:focus, .btn-text-1:hover, .btn-text-1:focus {
						color: ' . bloghash_sanitize_color( bloghash_option( 'text_button_hover_text_color' ) ) . ';
					}

					.bloghash-btn.btn-text-1 > span::before {
						background-color: ' . bloghash_sanitize_color( bloghash_option( 'text_button_hover_text_color' ) ) . ';
					}
				';
			}

			// Secondary button typography.
			$css .= $this->get_typography_field_css( '.bloghash-btn.btn-text-1, .btn-text-1', 'text_button_typography' );

			// Return the filtered CSS.
			return $css;
		}

		/**
		 * Generate dynamic Block Editor styles.
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function get_block_editor_css() {

			// Current post.
			$post_id   = get_the_ID();
			$post_type = get_post_type( $post_id );

			// Layout.
			$site_layout          = bloghash_get_site_layout( $post_id );
			$sidebar_position     = bloghash_get_sidebar_position( $post_id );
			$container_width      = bloghash_option( 'container_width' );
			$single_content_width = bloghash_option( 'single_content_width' );

			$container_width = $container_width - 100;

			if ( bloghash_is_sidebar_displayed( $post_id ) ) {

				$sidebar_width   = bloghash_option( 'sidebar_width' );
				$container_width = $container_width * ( 100 - intval( $sidebar_width ) ) / 100;
				$container_width = $container_width - 50;

				if ( 'boxed-separated' === $site_layout ) {
					if ( 3 === intval( bloghash_option( 'sidebar_style' ) ) ) {
						$container_width += 15;
					}
				}
			}

			if ( 'boxed-separated' === $site_layout ) {
				$container_width += 16;
			}

			if ( 'boxed' === $site_layout ) {
				$container_width = $container_width + 200;
			}

			$background_color = get_background_color();
			$accent_color     = bloghash_option( 'accent_color' );
			$content_color    = bloghash_option( 'boxed_content_background_color' );
			$text_color       = bloghash_option( 'content_text_color' );
			$link_hover_color = bloghash_option( 'content_link_hover_color' );
			$headings_color   = bloghash_option( 'headings_color' );
			$font_smoothing   = bloghash_option( 'font_smoothing' );

			$css = '';

			// Base HTML font size.
			$css .= $this->get_range_field_css( 'html', 'font-size', 'html_base_font_size', true, '%' );

			// Accent color.
			$css .= '
				.editor-styles-wrapper .block-editor-rich-text__editable mark,
				.editor-styles-wrapper .block-editor-rich-text__editable span.highlight,
				.editor-styles-wrapper .block-editor-rich-text__editable code,
				.editor-styles-wrapper .block-editor-rich-text__editable kbd,
				.editor-styles-wrapper .block-editor-rich-text__editable var,
				.editor-styles-wrapper .block-editor-rich-text__editable samp,
				.editor-styles-wrapper .block-editor-rich-text__editable tt {
					background-color: ' . bloghash_sanitize_color( bloghash_hex2rgba( $accent_color, .09 ) ) . ';
				}

				.editor-styles-wrapper .wp-block code.block,
				.editor-styles-wrapper .block code {
					background-color: ' . bloghash_sanitize_color( bloghash_hex2rgba( $accent_color, .075 ) ) . ';
				}

				.editor-styles-wrapper .wp-block .block-editor-rich-text__editable a,
				.editor-styles-wrapper .block-editor-rich-text__editable code,
				.editor-styles-wrapper .block-editor-rich-text__editable kbd,
				.editor-styles-wrapper .block-editor-rich-text__editable var,
				.editor-styles-wrapper .block-editor-rich-text__editable samp,
				.editor-styles-wrapper .block-editor-rich-text__editable tt {
					color: ' . bloghash_sanitize_color( $accent_color ) . ';
				}

				#editor .editor-styles-wrapper ::-moz-selection { background-color: ' . bloghash_sanitize_color( $accent_color ) . '; color: #FFF; }
				#editor .editor-styles-wrapper ::selection { background-color: ' . bloghash_sanitize_color( $accent_color ) . '; color: #FFF; }

				
				.editor-styles-wrapper blockquote,
				.editor-styles-wrapper .wp-block-quote {
					border-color: ' . bloghash_sanitize_color( $accent_color ) . ';
				}
			';

			// Container width.
			/*
			if ( 'fw-stretched' === $site_layout ) {
				$css .= '
					.editor-styles-wrapper .wp-block {
						max-width: none;
					}
				';
			} elseif ( 'boxed-separated' === $site_layout || 'boxed' === $site_layout ) {

				$css .= '
					.editor-styles-wrapper {
						max-width: ' . $container_width . 'px;
						margin: 0 auto;
					}

					.editor-styles-wrapper .wp-block {
						max-width: none;
					}
				';

				if ( 'boxed' === $site_layout ) {
					$css .= '
						.editor-styles-wrapper {
							-webkit-box-shadow: 0 0 30px rgba(50, 52, 54, 0.06);
							box-shadow: 0 0 30px rgba(50, 52, 54, 0.06);
							padding-left: 42px;
							padding-right: 42px;
						}
					';
				} else {
					$css .= '
						.editor-styles-wrapper {
							border-radius: 3px;
							border: 1px solid rgba(185, 185, 185, 0.4);
						}
					';
				}
			}
			else {
				$css .= '
					.editor-styles-wrapper .wp-block {
						max-width: ' . $container_width . 'px;
					}
				';
			} */

			if ( 'boxed-separated' === $site_layout || 'boxed' === $site_layout ) {

				if ( 'boxed' === $site_layout ) {
					$css .= '
						.editor-styles-wrapper {
							-webkit-box-shadow: 0 0 30px rgba(50, 52, 54, 0.06);
							box-shadow: 0 0 30px rgba(50, 52, 54, 0.06);
							padding-left: 42px;
							padding-right: 42px;
						}
					';
				} else {
					$css .= '
						.editor-styles-wrapper {
							border-radius: 0;
							border: 1px solid rgba(185, 185, 185, 0.4);
						}
					';
				}
			}

			if ( 'post' === $post_type && 'narrow' === $single_content_width ) {

				$narrow_container_width = bloghash_option( 'single_narrow_container_width' );

				$css .= '
					.editor-styles-wrapper .wp-block:not([data-size="full"]) {
						max-width: ' . intval( $narrow_container_width ) . 'px;
					}
				';
			}

			// Background color.
			if ( 'boxed-separated' === $site_layout || 'boxed' === $site_layout ) {
				$css .= '
					:root .edit-post-layout .interface-interface-skeleton__content {
						background-color: ' . bloghash_sanitize_color( $background_color ) . ';
					}

					:root .editor-styles-wrapper {
						background-color: ' . bloghash_sanitize_color( $content_color ) . ';
					}
				';
			} else {
				$css .= '
					:root .editor-styles-wrapper {
						background-color: ' . bloghash_sanitize_color( $background_color ) . ';
					}
				';
			}

			// Body.
			$css .= $this->get_typography_field_css( ':root .editor-styles-wrapper, .editor-styles-wrapper .wp-block, .block-editor-default-block-appender textarea.block-editor-default-block-appender__content', 'body_font' );
			$css .= '
				:root .editor-styles-wrapper {
					color: ' . bloghash_sanitize_color( $text_color ) . ';
				}
			';

			// If single post, use single post font size settings.
			if ( 'post' === $post_type ) {
				$css .= $this->get_range_field_css( ':root .editor-styles-wrapper .wp-block', 'font-size', 'single_content_font_size', true );
			}

			// Headings typography.
			$css .= $this->get_typography_field_css( ':root .editor-styles-wrapper h1.wp-block, :root .editor-styles-wrapper h2.wp-block, :root .editor-styles-wrapper h3.wp-block, :root .editor-styles-wrapper h4.wp-block, :root .editor-styles-wrapper h5.wp-block, :root .editor-styles-wrapper h6.wp-block, :root .editor-styles-wrapper .editor-post-title__block .editor-post-title__input', 'headings_font' );

			// Heading em.
			$css .= $this->get_typography_field_css( '.editor-styles-wrapper h1.wp-block em, .editor-styles-wrapper h2.wp-block em, .editor-styles-wrapper h3.wp-block em, .editor-styles-wrapper h4.wp-block em, .editor-styles-wrapper h5.wp-block em, .editor-styles-wrapper h6.wp-block em', 'heading_em_font' );

			// Headings (H1-H6).
			$css .= $this->get_typography_field_css( ':root .editor-styles-wrapper h1.wp-block, :root .editor-styles-wrapper .h1, :root .editor-styles-wrapper .editor-post-title__block .editor-post-title__input', 'h1_font' );
			$css .= $this->get_typography_field_css( ':root .editor-styles-wrapper h2.wp-block, :root .editor-styles-wrapper .h2', 'h2_font' );
			$css .= $this->get_typography_field_css( ':root .editor-styles-wrapper h3.wp-block, :root .editor-styles-wrapper .h3', 'h3_font' );
			$css .= $this->get_typography_field_css( ':root .editor-styles-wrapper h4.wp-block', 'h4_font' );
			$css .= $this->get_typography_field_css( ':root .editor-styles-wrapper h5.wp-block', 'h5_font' );
			$css .= $this->get_typography_field_css( ':root .editor-styles-wrapper h6.wp-block', 'h6_font' );

			$css .= '
				:root a,
				:root .editor-styles-wrapper h1,
				:root .editor-styles-wrapper h2,
				:root .editor-styles-wrapper h3,
				:root .editor-styles-wrapper h4,
				:root .editor-styles-wrapper .h4,
				:root .editor-styles-wrapper h5,
				:root .editor-styles-wrapper h6,
				:root .editor-post-title__block .editor-post-title__input {
					color: ' . bloghash_sanitize_color( $headings_color ) . ';
				}
			';

			// Page header font size.
			$css .= $this->get_range_field_css( ':root .editor-styles-wrapper .editor-post-title__block .editor-post-title__input', 'font-size', 'page_header_font_size', true );

			// Link hover color.
			$css .= '
				.editor-styles-wrapper .wp-block .block-editor-rich-text__editable a:hover { 
					color: ' . bloghash_sanitize_color( $link_hover_color ) . '; 
				}
			';

			// Font smoothing.
			if ( $font_smoothing ) {
				$css .= '
					.editor-styles-wrapper {
						-moz-osx-font-smoothing: grayscale;
						-webkit-font-smoothing: antialiased;
					}
				';
			}

			return $css;
		}
	}
endif;

/**
 * The function which returns the one Bloghash_Dynamic_Styles instance.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $dynamic_styles = bloghash_dynamic_styles(); ?>
 *
 * @since 1.0.0
 * @return object
 */
function bloghash_dynamic_styles() {
	return Bloghash_Dynamic_Styles::instance();
}

bloghash_dynamic_styles();
