<?php
/**
 * Bloglamp Admin class. Bloglamp related pages in WP Dashboard.
 *
 * @package Bloglamp
 * @author  Peregrine Themes <peregrinethemes@gmail.com>
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bloglamp Admin Class.
 *
 * @since 1.0.0
 * @package Bloglamp
 */
final class Bloglamp_Customizer {

	/**
	 * Singleton instance of the class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private static $instance;

	/**
	 * Main Bloglamp Admin Instance.
	 *
	 * @since 1.0.0
	 * @return Bloglamp_Customizer
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Bloglamp_Customizer ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

		// Init Bloglamp admin.
		add_action( 'init', array( $this, 'includes' ) );

		// Bloglamp Admin loaded.
		do_action( 'Bloglamp_Customizer_loaded' );
	}

	/**
	 * Include files.
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		require_once get_stylesheet_directory() . '/inc/customizer/settings/index.php';
		require_once get_stylesheet_directory() . '/inc/customizer/default.php';
	}

}

/**
 * The function which returns the one Bloglamp_Customizer instance.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $Bloglamp_Customizer = Bloglamp_Customizer(); ?>
 *
 * @since 1.0.0
 * @return object
 */
function Bloglamp_Customizer() {
	return Bloglamp_Customizer::instance();
}

Bloglamp_Customizer();
