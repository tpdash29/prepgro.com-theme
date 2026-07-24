<?php
/**
 * Theme options — lightweight Customizer panel (no hard ACF dependency).
 *
 * A1/A4 stub: registers a Branding section with a primary-colour control that
 * feeds the token loader's :root override. Logo/country/layout controls and the
 * block-pattern picker are completed in E3.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers theme Customizer controls.
 */
final class Theme_Options {

	/** @var Theme_Options|null */
	private static $instance = null;

	/**
	 * @return Theme_Options
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'customize_register', array( $this, 'register' ) );
		// Emit the branding overrides as inline CSS attached to the MAIN theme
		// stylesheet (pgt-theme) at priority 20 — after functions.php enqueues
		// pgt-theme at 10. This is load-bearing: theme.css hard-declares
		// :root{--pgt-logo-h:34px} and _semantic.css declares
		// --pge-color-primary, so an override printed BEFORE theme.css (e.g. via
		// wp_head or on the earlier pge-tokens handle) loses the cascade. Printing
		// AFTER theme.css guarantees the Customizer values win.
		add_action( 'wp_enqueue_scripts', array( $this, 'output_branding_overrides' ), 20 );
	}

	/**
	 * Emit the Customizer branding overrides (logo height + primary colour) as
	 * inline CSS after the main stylesheet, so they win the cascade.
	 *
	 * theme.css sizes the chip, wordmark and any uploaded custom logo from
	 * --pgt-logo-h, so one slider scales the whole lockup proportionally;
	 * .pgt-brandlogo__gro (and the rest of the UI) reads --pge-color-primary.
	 *
	 * @return void
	 */
	public function output_branding_overrides() {
		$vars = array();

		$primary = sanitize_hex_color( (string) get_theme_mod( 'pge_brand_primary', '' ) );
		if ( $primary ) {
			$vars[] = '--pge-color-primary:' . $primary . ';';
		}

		$h      = (int) get_theme_mod( 'pgt_logo_height', 34 );
		$h      = max( 24, min( 64, $h ) );
		$vars[] = '--pgt-logo-h:' . $h . 'px;';

		wp_add_inline_style( 'pgt-theme', ':root{' . implode( '', $vars ) . '}' );
	}

	/**
	 * Register the Branding section + primary colour control.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	public function register( $wp_customize ) {
		$wp_customize->add_section(
			'pge_branding',
			array(
				'title'    => __( 'PrepGro Branding', 'prepgro-theme' ),
				'priority' => 30,
			)
		);

		$wp_customize->add_setting(
			'pge_brand_primary',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'pge_brand_primary',
				array(
					'label'       => __( 'Primary colour', 'prepgro-theme' ),
					'description' => __( 'Overrides --pge-color-primary. Leave empty to use the theme default.', 'prepgro-theme' ),
					'section'     => 'pge_branding',
				)
			)
		);

		// Header logo size — scales the brand-kit chip + wordmark (or an
		// uploaded custom logo) proportionally. Default 34px chip height.
		$wp_customize->add_setting(
			'pgt_logo_height',
			array(
				'default'           => 34,
				'sanitize_callback' => function ( $value ) {
					return max( 24, min( 64, (int) $value ) );
				},
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'pgt_logo_height',
			array(
				'label'       => __( 'Logo size (px)', 'prepgro-theme' ),
				'description' => __( 'Height of the header logo. 34px is the brand-kit default.', 'prepgro-theme' ),
				'section'     => 'pge_branding',
				'type'        => 'range',
				'input_attrs' => array(
					'min'  => 24,
					'max'  => 64,
					'step' => 1,
				),
			)
		);
	}
}
