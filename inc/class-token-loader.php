<?php
/**
 * Token loader — enqueues the --pge-* design-token stylesheet.
 *
 * The PrepGro Engine plugin styles its product UI against semantic tokens
 * (var(--pge-color-primary, ...)). This loader supplies those tokens so the
 * plugin renders correctly under this theme. Per-country brand overrides layer
 * on top (theme option / child theme) without touching plugin component CSS.
 *
 * A1/A4 stub: loads the default brand bundle. The full token pipeline
 * (theme.json -> tokens, option-driven :root overrides) is finished in E1.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues design tokens on the front end and in the block editor.
 */
final class Token_Loader {

	/** @var Token_Loader|null */
	private static $instance = null;

	const HANDLE = 'pge-tokens';

	/**
	 * @return Token_Loader
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 5 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ), 5 );
	}

	/**
	 * Enqueue the token bundle, then print any option-driven :root overrides.
	 *
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_style(
			self::HANDLE,
			PGT_URI . '/assets/css/tokens/brand-default.css',
			array(),
			PGT_VERSION
		);

		$inline = $this->brand_override_css();
		if ( '' !== $inline ) {
			wp_add_inline_style( self::HANDLE, $inline );
		}
	}

	/**
	 * Build a :root override block from theme options (e.g. primary colour).
	 * Empty in the stub when no override is set — defaults come from the token files.
	 *
	 * @return string
	 */
	private function brand_override_css() {
		$primary = get_theme_mod( 'pge_brand_primary', '' );
		if ( '' === $primary ) {
			return '';
		}

		$primary = sanitize_hex_color( $primary );
		if ( ! $primary ) {
			return '';
		}

		return sprintf( ':root{--pge-color-primary:%s;}', $primary );
	}
}
