<?php
/**
 * Theme options — lightweight Customizer panel (no hard ACF dependency).
 *
 * Two areas:
 *   - PrepGro Branding: primary colour + logo size, feeding the token loader.
 *   - PrepGro Images: every editable photograph on the public site, driven
 *     entirely by the Image_Slots registry. Adding a photo position anywhere
 *     needs no edit here — register it and its section appears.
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
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'controls_assets' ) );
		/*
		 * Priority 30, and the number is load-bearing.
		 *
		 * wp_add_inline_style() returns false and does nothing if its handle is
		 * not registered YET — it does not queue for later. functions.php
		 * enqueues pgt-theme on this same hook at priority 20, and this class's
		 * init() runs earlier in functions.php than that add_action() call, so
		 * at an equal priority WordPress runs THIS callback first (same
		 * priority = registration order) and the inline style vanishes with no
		 * warning.
		 *
		 * That is exactly what was happening. This sat at 20 behind a comment
		 * claiming the stylesheet enqueued at 10 — true once, but functions.php
		 * later moved it to 20 — so "Logo size" and "Primary colour" silently
		 * did nothing on the front end. If that enqueue moves again, this
		 * number has to stay above it.
		 *
		 * It must also come AFTER, not before: theme.css hard-declares
		 * :root{--pgt-logo-h:34px} and _semantic.css declares
		 * --pge-color-primary, so an override printed earlier loses the cascade.
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'output_branding_overrides' ), 30 );
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

		$h      = (int) get_theme_mod( 'pgt_logo_height', self::LOGO_DEFAULT );
		$h      = max( 24, min( 72, $h ) );
		$vars[] = '--pgt-logo-h:' . $h . 'px;';

		$nav = (int) get_theme_mod( 'pgt_nav_font_size', 145 );
		$nav = max( 120, min( 180, $nav ) );
		$vars[] = '--pgt-nav-size:' . ( $nav / 10 ) . 'px;';

		$stack = self::font_stack( (string) get_theme_mod( 'pgt_nav_font', 'heading' ) );
		if ( '' !== $stack ) {
			$vars[] = '--pgt-nav-font:' . $stack . ';';
		}

		// Header height, background and text colour.
		$header_h = (int) get_theme_mod( 'pgt_header_height', self::HEADER_HEIGHT_DEFAULT );
		$header_h = max( 56, min( 120, $header_h ) );
		$vars[]   = '--pgt-header-h:' . $header_h . 'px;';

		$header_bg = sanitize_hex_color( (string) get_theme_mod( 'pgt_header_bg', '' ) );
		if ( $header_bg ) {
			$vars[] = '--pgt-header-bg:' . $header_bg . ';';
		}

		$header_fg = sanitize_hex_color( (string) get_theme_mod( 'pgt_header_fg', '' ) );
		if ( $header_fg ) {
			$vars[] = '--pgt-header-fg:' . $header_fg . ';';
		}

		// Footer background, text colour, link size and minimum height.
		$footer_bg = sanitize_hex_color( (string) get_theme_mod( 'pgt_footer_bg', '' ) );
		if ( $footer_bg ) {
			$vars[] = '--pgt-footer-bg:' . $footer_bg . ';';
		}

		$footer_fg = sanitize_hex_color( (string) get_theme_mod( 'pgt_footer_fg', '' ) );
		if ( $footer_fg ) {
			$vars[] = '--pgt-footer-fg:' . $footer_fg . ';';
		}

		$footer_fs = (int) get_theme_mod( 'pgt_footer_font_size', 140 );
		$footer_fs = max( 120, min( 180, $footer_fs ) );
		$vars[]    = '--pgt-footer-fs:' . ( $footer_fs / 10 ) . 'px;';

		$footer_min_h = (int) get_theme_mod( 'pgt_footer_min_height', 0 );
		$footer_min_h = max( 0, min( 480, $footer_min_h ) );
		if ( $footer_min_h > 0 ) {
			$vars[] = '--pgt-footer-min-h:' . $footer_min_h . 'px;';
		}

		// Per-pillar accents go at :root as --pgt-pillar-*, the single source
		// both consumers read: the nav links (theme.css, every page) and the
		// module pages' --pgm-accent (pg-module.css derives from these rather
		// than restating them). Overriding one variable therefore moves the
		// menu and the module page together instead of letting them drift.
		foreach ( self::pillar_defaults() as $key => $fallback ) {
			$hex = sanitize_hex_color( (string) get_theme_mod( 'pgt_accent_' . $key, '' ) );
			if ( ! $hex ) {
				continue;
			}
			$vars[] = '--pgt-pillar-' . $key . ':' . $hex . ';';
		}

		wp_add_inline_style( 'pgt-theme', ':root{' . implode( '', $vars ) . '}' );
	}

	/**
	 * Default header logo height in px.
	 *
	 * Raised from 30 to 38 on 2026-08-05 — at 30 the lockup read small
	 * against a 40px CTA pill. Everything in the lockup scales off
	 * --pgt-logo-h (chip, wordmark, and the module label's fit under "Gro"),
	 * so this one number moves the whole thing proportionally.
	 */
	const LOGO_DEFAULT = 38;

	/**
	 * Default header bar height in px — matches the shipped
	 * `.pgt-header__inner { min-height }` value in theme.css.
	 */
	const HEADER_HEIGHT_DEFAULT = 74;

	/**
	 * The pillar accent keys and the values pg-module.css ships as defaults —
	 * used as the colour picker's starting point so the control opens showing
	 * what is actually on screen.
	 *
	 * @return array
	 */
	private static function pillar_defaults() {
		return array(
			'evaluate' => '#1b3beb',
			'elevate'  => '#0f766e',
			'excel'    => '#b45309',
		);
	}

	/**
	 * Resolve a nav font choice to a CSS stack.
	 *
	 * Deliberately a short list of what the theme already loads rather than a
	 * free-text field or a web-font picker: an arbitrary family would either
	 * not be loaded (silently falling back) or would need a new network
	 * request on every page.
	 *
	 * @param string $choice Setting value.
	 * @return string CSS font-family value, or '' to leave the default alone.
	 */
	private static function font_stack( $choice ) {
		$stacks = array(
			'heading' => '',
			'body'    => 'var(--pge-font-body)',
			'system'  => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
			'mono'    => 'var(--pge-font-mono)',
		);
		return isset( $stacks[ $choice ] ) ? $stacks[ $choice ] : '';
	}

	/**
	 * Styling + the copy-to-clipboard behaviour for the prompt boxes.
	 *
	 * Attached to core's `customize-controls` handles rather than shipping a
	 * file, since it is a few lines and only ever runs inside the Customizer.
	 *
	 * @return void
	 */
	public function controls_assets() {
		$css = '
			.pgt-promptbox { margin-bottom: 4px; }
			.pgt-promptbox__where { margin: 0 0 6px; color: #50575e; }
			.pgt-promptbox__size { margin: 0 0 10px; }
			.pgt-promptbox__text {
				width: 100%; font-family: Menlo, Consolas, monospace; font-size: 11px;
				line-height: 1.55; background: #f6f7f7; border: 1px solid #dcdcde;
				border-radius: 4px; padding: 8px; resize: vertical;
			}
			.pgt-promptbox__copy { margin-top: 8px; }
			.pgt-imgslot-count {
				margin: 0 0 10px; padding: 6px 10px; border-radius: 4px;
				background: #f0f6fc; border-left: 3px solid #72aee6; font-size: 12px;
			}
			.pgt-imgslot-count.is-empty { background: #fcf9e8; border-left-color: #dba617; }
		';
		wp_add_inline_style( 'customize-controls', $css );

		$js = '
			document.addEventListener( "click", function ( e ) {
				var btn = e.target.closest && e.target.closest( ".pgt-promptbox__copy" );
				if ( ! btn ) { return; }
				var box = document.getElementById( btn.getAttribute( "data-target" ) );
				if ( ! box ) { return; }
				var done = function () {
					var was = btn.textContent;
					btn.textContent = "Copied";
					setTimeout( function () { btn.textContent = was; }, 1400 );
				};
				// navigator.clipboard needs a secure context, which a local or
				// plain-http admin is not — fall back to the selection copy.
				if ( navigator.clipboard && window.isSecureContext ) {
					navigator.clipboard.writeText( box.value ).then( done );
				} else {
					box.select();
					try { document.execCommand( "copy" ); done(); } catch ( err ) {}
				}
			} );
		';
		wp_add_inline_script( 'customize-controls', $js );
	}

	/**
	 * Register the Branding section + the registry-driven Images panel.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	public function register( $wp_customize ) {
		// Loaded here, not at file scope: WP_Customize_Control only exists
		// once the Customizer has booted, and both custom controls below
		// extend it.
		require_once PGT_DIR . '/inc/class-customize-image-prompt-control.php';

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

		// Header logo size — scales the brand-kit chip, the wordmark, an
		// uploaded custom logo AND the module label's fit under "Gro", since
		// all four derive from --pgt-logo-h.
		$wp_customize->add_setting(
			'pgt_logo_height',
			array(
				'default'           => self::LOGO_DEFAULT,
				'sanitize_callback' => function ( $value ) {
					return max( 24, min( 72, (int) $value ) );
				},
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'pgt_logo_height',
			array(
				'label'       => __( 'Logo size (px)', 'prepgro-theme' ),
				'description' => __( 'Height of the header logo. Everything in the lockup scales with it. 38px is the default.', 'prepgro-theme' ),
				'section'     => 'pge_branding',
				'type'        => 'range',
				'input_attrs' => array(
					'min'  => 24,
					'max'  => 72,
					'step' => 1,
				),
			)
		);

		// Point at core's own logo upload rather than adding a second one —
		// two controls writing different settings is how a site ends up with
		// a logo set that nothing renders.
		$wp_customize->add_control(
			new Customize_Note_Control(
				$wp_customize,
				'pgt_logo_note',
				array(
					'section'  => 'pge_branding',
					'settings' => array(),
					'priority' => 5,
					'note'     => __( 'To replace the logo image itself, use Site Identity → Logo. Leave it empty to keep the built-in prepGro lockup.', 'prepgro-theme' ),
				)
			)
		);

		$this->register_header_controls( $wp_customize );
		$this->register_footer_controls( $wp_customize );
		$this->register_pillar_colours( $wp_customize );
		$this->register_image_slots( $wp_customize );
	}

	/**
	 * Header bar — height, background, text colour, and (moved here from the
	 * old flat Branding section) the menu font family/size.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	private function register_header_controls( $wp_customize ) {
		$wp_customize->add_section(
			'pgt_header',
			array(
				'title'    => __( 'PrepGro Header', 'prepgro-theme' ),
				'priority' => 31,
			)
		);

		$wp_customize->add_setting(
			'pgt_header_height',
			array(
				'default'           => self::HEADER_HEIGHT_DEFAULT,
				'sanitize_callback' => function ( $value ) {
					return max( 56, min( 120, (int) $value ) );
				},
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'pgt_header_height',
			array(
				'label'       => __( 'Header height (px)', 'prepgro-theme' ),
				'description' => __( '74px is the default. Range 56–120px.', 'prepgro-theme' ),
				'section'     => 'pgt_header',
				'type'        => 'range',
				'input_attrs' => array(
					'min'  => 56,
					'max'  => 120,
					'step' => 1,
				),
			)
		);

		$wp_customize->add_setting(
			'pgt_header_bg',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'pgt_header_bg',
				array(
					'label'       => __( 'Header background', 'prepgro-theme' ),
					'description' => __( 'Leave empty for the default, white.', 'prepgro-theme' ),
					'section'     => 'pgt_header',
				)
			)
		);

		$wp_customize->add_setting(
			'pgt_header_fg',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'pgt_header_fg',
				array(
					'label'       => __( 'Header text & icon colour', 'prepgro-theme' ),
					'description' => __( 'The menu\'s Home/Help items, the icon buttons and the account button. The three pillar menu items (Evaluate/Elevate/Excel) keep their own colours from PrepGro Pillar Colours below.', 'prepgro-theme' ),
					'section'     => 'pgt_header',
				)
			)
		);

		$this->register_menu_type( $wp_customize );
	}

	/**
	 * Footer band — background, text colour, link size and minimum height.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	private function register_footer_controls( $wp_customize ) {
		$wp_customize->add_section(
			'pgt_footer',
			array(
				'title'    => __( 'PrepGro Footer', 'prepgro-theme' ),
				'priority' => 33,
			)
		);

		$wp_customize->add_setting(
			'pgt_footer_min_height',
			array(
				'default'           => 0,
				'sanitize_callback' => function ( $value ) {
					return max( 0, min( 480, (int) $value ) );
				},
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'pgt_footer_min_height',
			array(
				'label'       => __( 'Footer minimum height (px)', 'prepgro-theme' ),
				'description' => __( 'The footer\'s height already follows its content; this only raises a floor under it. 0 leaves it fully automatic.', 'prepgro-theme' ),
				'section'     => 'pgt_footer',
				'type'        => 'range',
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 480,
					'step' => 10,
				),
			)
		);

		$wp_customize->add_setting(
			'pgt_footer_bg',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'pgt_footer_bg',
				array(
					'label'       => __( 'Footer background', 'prepgro-theme' ),
					'description' => __( 'Leave empty for the default, light grey with a dotted texture. The texture stays even when this is set.', 'prepgro-theme' ),
					'section'     => 'pgt_footer',
				)
			)
		);

		$wp_customize->add_setting(
			'pgt_footer_fg',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'pgt_footer_fg',
				array(
					'label'       => __( 'Footer text colour', 'prepgro-theme' ),
					'description' => __( 'The brand statement and the column links. The copyright row stays a lighter, secondary tone by design.', 'prepgro-theme' ),
					'section'     => 'pgt_footer',
				)
			)
		);

		// Stored x10, same convention as the header menu font size below.
		$wp_customize->add_setting(
			'pgt_footer_font_size',
			array(
				'default'           => 140,
				'sanitize_callback' => function ( $value ) {
					return max( 120, min( 180, (int) $value ) );
				},
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'pgt_footer_font_size',
			array(
				'label'       => __( 'Footer link size (×10 px)', 'prepgro-theme' ),
				'description' => __( '140 = 14px, the default. Range 12–18px.', 'prepgro-theme' ),
				'section'     => 'pgt_footer',
				'type'        => 'range',
				'input_attrs' => array(
					'min'  => 120,
					'max'  => 180,
					'step' => 5,
				),
			)
		);
	}

	/**
	 * Menu typography — family and size.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	private function register_menu_type( $wp_customize ) {
		$wp_customize->add_setting(
			'pgt_nav_font',
			array(
				'default'           => 'heading',
				'sanitize_callback' => function ( $value ) {
					return in_array( $value, array( 'heading', 'body', 'system', 'mono' ), true ) ? $value : 'heading';
				},
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'pgt_nav_font',
			array(
				'label'       => __( 'Menu font', 'prepgro-theme' ),
				'description' => __( 'Limited to families the theme already loads — anything else would need a new web-font request on every page.', 'prepgro-theme' ),
				'section'     => 'pgt_header',
				'type'        => 'select',
				'choices'     => array(
					'heading' => __( 'Outfit (theme default)', 'prepgro-theme' ),
					'body'    => __( 'Body font', 'prepgro-theme' ),
					'system'  => __( 'System UI font', 'prepgro-theme' ),
					'mono'    => __( 'Monospace', 'prepgro-theme' ),
				),
			)
		);

		// Stored x10 so the range control can step in 0.5px increments while
		// remaining an integer setting.
		$wp_customize->add_setting(
			'pgt_nav_font_size',
			array(
				'default'           => 145,
				'sanitize_callback' => function ( $value ) {
					return max( 120, min( 180, (int) $value ) );
				},
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'pgt_nav_font_size',
			array(
				'label'       => __( 'Menu font size (×10 px)', 'prepgro-theme' ),
				'description' => __( '145 = 14.5px, the default. Range 12–18px.', 'prepgro-theme' ),
				'section'     => 'pgt_header',
				'type'        => 'range',
				'input_attrs' => array(
					'min'  => 120,
					'max'  => 180,
					'step' => 5,
				),
			)
		);
	}

	/**
	 * Per-pillar accent colours — the hues that drive buttons, links, icon
	 * chips and the rule under the menu on each module page.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	private function register_pillar_colours( $wp_customize ) {
		$wp_customize->add_section(
			'pgt_pillar_colours',
			array(
				'title'       => __( 'PrepGro Pillar Colours', 'prepgro-theme' ),
				'description' => __( 'One hue per pillar, used site-wide: that pillar\'s item in the main menu on every page, plus its module page\'s buttons, links, icon chips, graphs, the name under the logo and the rule under the menu. Pick colours dark enough to carry white button text and to read as a menu link — aim for a 4.5:1 contrast ratio on white.', 'prepgro-theme' ),
				'priority'    => 32,
			)
		);

		$labels = array(
			'evaluate' => __( 'Evaluate', 'prepgro-theme' ),
			'elevate'  => __( 'Elevate', 'prepgro-theme' ),
			'excel'    => __( 'Excel', 'prepgro-theme' ),
		);

		foreach ( self::pillar_defaults() as $key => $default ) {
			$wp_customize->add_setting(
				'pgt_accent_' . $key,
				array(
					'default'           => '',
					'sanitize_callback' => 'sanitize_hex_color',
					'transport'         => 'refresh',
				)
			);

			$wp_customize->add_control(
				new \WP_Customize_Color_Control(
					$wp_customize,
					'pgt_accent_' . $key,
					array(
						'label'       => $labels[ $key ],
						/* translators: %s: the default hex colour for this pillar */
						'description' => sprintf( __( 'Leave empty for the default, %s.', 'prepgro-theme' ), $default ),
						'section'     => 'pgt_pillar_colours',
					)
				)
			);
		}
	}

	/**
	 * Build the Images panel from the slot registry.
	 *
	 * One SECTION per photo position, each holding the country-aware prompt
	 * followed by up to Image_Slots::MAX uploads. A section per slot rather than
	 * one long section because five slots times five uploads is thirty controls
	 * — grouped, that is navigable; flat, it is a wall.
	 *
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	private function register_image_slots( $wp_customize ) {
		$slots = Image_Slots::slots();
		if ( ! $slots ) {
			return;
		}

		$wp_customize->add_panel(
			'pgt_images',
			array(
				'title'       => __( 'PrepGro Images', 'prepgro-theme' ),
				'description' => __( 'Every photograph on the public site. Each position takes up to five images and shows a different one on each page load. Leave a position empty and the layout still holds — it falls back to a brand tint.', 'prepgro-theme' ),
				'priority'    => 34,
			)
		);

		$priority = 10;

		foreach ( array_keys( $slots ) as $key ) {
			$slot = Image_Slots::get( $key );
			if ( ! $slot ) {
				continue;
			}

			$section_id = 'pgt_img_' . $key;

			$wp_customize->add_section(
				$section_id,
				array(
					'title'    => $slot['label'],
					'panel'    => 'pgt_images',
					'priority' => $priority,
				)
			);
			$priority += 10;

			// `settings => array()` makes this a display-only control: it shows
			// the art direction, the size and the prompt, and saves nothing.
			// `slot_key` is passed as an arg because WP_Customize_Control's
			// constructor assigns any arg matching a declared property.
			$wp_customize->add_control(
				new Customize_Image_Prompt_Control(
					$wp_customize,
					$section_id . '_prompt',
					array(
						'section'  => $section_id,
						'settings' => array(),
						'priority' => 1,
						'slot_key' => $key,
					)
				)
			);

			$n = 1;
			foreach ( Image_Slots::setting_keys( $key ) as $setting_key ) {
				$wp_customize->add_setting(
					$setting_key,
					array(
						'default'           => 0,
						'sanitize_callback' => 'absint',
						'transport'         => 'refresh',
					)
				);

				$wp_customize->add_control(
					new \WP_Customize_Media_Control(
						$wp_customize,
						$setting_key,
						array(
							'label'     => sprintf(
								/* translators: 1: image number, 2: total images allowed */
								__( 'Image %1$d of %2$d', 'prepgro-theme' ),
								$n,
								(int) Image_Slots::MAX
							),
							'section'   => $section_id,
							'mime_type' => 'image',
							'priority'  => 10 + $n,
						)
					)
				);
				$n++;
			}
		}
	}
}
