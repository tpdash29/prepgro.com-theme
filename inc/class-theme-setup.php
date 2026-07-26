<?php
/**
 * Theme setup — supports, menus, and editor wiring.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers theme supports and core wiring.
 */
final class Theme_Setup {

	/** @var Theme_Setup|null */
	private static $instance = null;

	/**
	 * @return Theme_Setup
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
		add_action( 'after_setup_theme', array( $this, 'add_theme_supports' ) );
		add_action( 'after_setup_theme', array( $this, 'register_menus' ) );
		add_action( 'after_setup_theme', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Classic menu locations consumed by the Chrome header/footer. Registering
	 * them re-enables Appearance → Menus in this block theme so the owner can
	 * edit chrome navigation without code. Chrome falls back to its built-in
	 * defaults when a location has no menu assigned, and the engine plugin
	 * seeds editable menus into these locations on activation
	 * (PrepGro\Engine\Menu_Seeder).
	 *
	 * @return void
	 */
	public function register_menus() {
		register_nav_menus(
			array(
				'pgt-header'         => __( 'Header — primary navigation', 'prepgro-theme' ),
				'pgt-footer-explore' => __( 'Footer — Explore column', 'prepgro-theme' ),
				'pgt-footer-support' => __( 'Footer — Support column', 'prepgro-theme' ),
				'pgt-footer-legal'   => __( 'Footer — Legal column', 'prepgro-theme' ),
			)
		);
	}

	/**
	 * Declare FSE + standard theme supports.
	 *
	 * @return void
	 */
	public function add_theme_supports() {
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'custom-logo' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_theme_textdomain( 'prepgro-theme', PGT_DIR . '/languages' );
	}
}
