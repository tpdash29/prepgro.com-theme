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

		$h      = (int) get_theme_mod( 'pgt_logo_height', 30 );
		$h      = max( 24, min( 64, $h ) );
		$vars[] = '--pgt-logo-h:' . $h . 'px;';

		wp_add_inline_style( 'pgt-theme', ':root{' . implode( '', $vars ) . '}' );
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
				'default'           => 30,
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
				'description' => __( 'Height of the header logo. 30px is the redesign default.', 'prepgro-theme' ),
				'section'     => 'pge_branding',
				'type'        => 'range',
				'input_attrs' => array(
					'min'  => 24,
					'max'  => 64,
					'step' => 1,
				),
			)
		);

		$this->register_image_slots( $wp_customize );
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

		require_once PGT_DIR . '/inc/class-customize-image-prompt-control.php';

		$wp_customize->add_panel(
			'pgt_images',
			array(
				'title'       => __( 'PrepGro Images', 'prepgro-theme' ),
				'description' => __( 'Every photograph on the public site. Each position takes up to five images and shows a different one on each page load. Leave a position empty and the layout still holds — it falls back to a brand tint.', 'prepgro-theme' ),
				'priority'    => 31,
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
