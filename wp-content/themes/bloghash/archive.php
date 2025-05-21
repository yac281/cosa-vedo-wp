<?php
/**
 * The template for displaying archive pages.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package BlogHash
 * @author Peregrine Themes
 * @since   1.0.0
 */

?>

<?php get_header(); ?>

<?php do_action( 'bloghash_before_container' ); ?>

<div class="bloghash-container">

	<?php do_action( 'bloghash_before_content_area', 'before_post_archive' ); ?>

	<div id="primary" class="content-area">

		<?php do_action( 'bloghash_before_content' ); ?>

		<main id="content" class="site-content" role="main"<?php bloghash_schema_markup( 'main' ); ?>>

			<?php do_action( 'bloghash_content_archive' ); ?>

		</main><!-- #content .site-content -->

		<?php do_action( 'bloghash_after_content' ); ?>

	</div><!-- #primary .content-area -->

	<?php do_action( 'bloghash_sidebar' ); ?>

	<?php do_action( 'bloghash_after_content_area' ); ?>

</div><!-- END .bloghash-container -->

<?php do_action( 'bloghash_after_container' ); ?>

<?php
get_footer();
