<?php //phpcs:ignore
/**
 * Theme functions and definitions.
 *
 * @package Bloglamp
 * @author  Peregrine Themes
 * @since   1.0.0
 */

/**
 * Main Bloglamp class.
 *
 * @since 1.0.0
 */
final class Bloglamp {

	/**
	 * Singleton instance of the class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Bloglamp ) ) {
			self::$instance = new Bloglamp();
			self::$instance->includes();
			// Hook now that all of the Bloglamp stuff is loaded.
			do_action( 'bloglamp_loaded' );
		}
		return self::$instance;
	}

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'bloglamp_styles' ) );
		add_filter( 'body_class', array( $this, 'bloglamp_body_classes' ) );
	}

	/**
	 * Include files.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function includes() {
		require get_stylesheet_directory() . '/inc/customizer/dynamic-styles.php';
		require get_stylesheet_directory() . '/inc/customizer/default.php';
		require get_stylesheet_directory() . '/inc/customizer/customizer.php';
	}

	/**
	 * Adds custom classes to the array of body classes.
	 *
	 * @since 1.0.0
	 * @param array $classes Classes for the body element.
	 * @return array
	 */
	function bloglamp_body_classes( $classes ) {
		// Site layout.
		$classes[] = 'bloglamp';

		return $classes;
	}

	/**
	 * Recommended way to include parent theme styles.
	 * (Please see http://codex.wordpress.org/Child_Themes#How_to_Create_a_Child_Theme)
	 */
	function bloglamp_styles() {
		wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
		wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' ) );
	}
}

/**
 * The function which returns the one Bloglamp instance.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $bloglamp = bloglamp(); ?>
 *
 * @since 1.0.0
 * @return object
 */
function bloglamp() {
	return Bloglamp::instance();
}

bloglamp();
