<?php
/**
 * Chrome — the site header (auth-aware) rendered as a shortcode so it can live
 * inside the block template part `parts/header.html` while staying dynamic
 * (login/register when logged out, dashboard when logged in) on both desktop
 * and mobile.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the responsive header.
 */
final class Chrome {

	/** @var Chrome|null */
	private static $instance = null;

	/**
	 * @return Chrome
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * @return void
	 */
	public function init() {
		add_shortcode( 'pge_header', array( $this, 'render_header' ) );
	}

	/**
	 * Primary nav links. Labels are country-aware when the engine is active.
	 *
	 * @return array<int,array{label:string,url:string}>
	 */
	private function nav_links() {
		$links = array(
			array( 'label' => __( 'Exams', 'prepgro-theme' ), 'url' => home_url( '/all-exams/' ) ),
			array( 'label' => __( 'How It Works', 'prepgro-theme' ), 'url' => home_url( '/#pg-how' ) ),
			array( 'label' => __( 'Find an Expert', 'prepgro-theme' ), 'url' => home_url( '/#pg-experts' ) ),
			array( 'label' => __( 'Pricing', 'prepgro-theme' ), 'url' => home_url( '/pricing/' ) ),
		);

		/**
		 * Filter the header nav links.
		 *
		 * @param array $links Nav link list.
		 */
		return apply_filters( 'pgt_header_nav_links', $links );
	}

	/**
	 * Render the header markup.
	 *
	 * @return string
	 */
	public function render_header() {
		$home = home_url( '/' );

		// Logo markup. get_custom_logo() ships its OWN <a class="custom-logo-link">,
		// so it must never be nested inside another anchor (the HTML parser
		// splits nested <a> tags apart, which drops the image out of .pgt-logo
		// and out of the size cap). We tag core's anchor with the pgt-logo
		// class instead. Falls back to the bundled brand-kit lockup (per
		// prepgro-logo-kit-v34) when no custom logo is set or its attachment
		// is missing. Output is a single line — the [pge_header] shortcode
		// runs through wpautop, which injects <br>/<p> into multi-line markup.
		$logo_html = '';
		if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
			$logo_html = (string) get_custom_logo();
		}
		if ( '' !== trim( $logo_html ) ) {
			$logo_html = str_replace( 'class="custom-logo-link', 'class="pgt-logo custom-logo-link', $logo_html );
		} else {
			$logo_html = '<a class="pgt-logo" href="' . esc_url( $home ) . '" rel="home">' . $this->brand_kit_logo() . '</a>';
		}
		$logo_html = trim( preg_replace( '/\s+/', ' ', $logo_html ) );

		$links = $this->nav_links();

		ob_start();
		?>
		<div class="pgt-header">
			<div class="pgt-header__inner">
				<?php
				// Not passed through wp_kses_post: kses strips the brand-kit
				// SVG chip (svg/defs/linearGradient aren't in its allowlist).
				// Both possible values are trusted markup we generate —
				// core's get_custom_logo() or brand_kit_logo() above.
				echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>

				<?php echo $this->country_chip(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<button class="pgt-burger" aria-label="<?php esc_attr_e( 'Open menu', 'prepgro-theme' ); ?>" aria-expanded="false" aria-controls="pgt-mobile">
					<span></span><span></span><span></span>
				</button>

				<nav class="pgt-nav" aria-label="<?php esc_attr_e( 'Primary', 'prepgro-theme' ); ?>">
					<?php foreach ( $links as $l ) : ?>
						<a class="pgt-nav__link" href="<?php echo esc_url( $l['url'] ); ?>"><?php echo esc_html( $l['label'] ); ?></a>
					<?php endforeach; ?>
				</nav>

				<div class="pgt-auth">
					<?php echo $this->auth_buttons(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>

			<!-- Mobile panel -->
			<div class="pgt-mobile" id="pgt-mobile" hidden>
				<nav class="pgt-mobile__nav" aria-label="<?php esc_attr_e( 'Mobile', 'prepgro-theme' ); ?>">
					<?php foreach ( $links as $l ) : ?>
						<a class="pgt-mobile__link" href="<?php echo esc_url( $l['url'] ); ?>"><?php echo esc_html( $l['label'] ); ?></a>
					<?php endforeach; ?>
				</nav>
				<div class="pgt-mobile__auth">
					<?php echo $this->auth_buttons(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Country chip — small flag + country code pill shown beside the logo.
	 *
	 * Reads the engine plugin's country of operation (PGE_COUNTRY, set in
	 * wp-config per the launch runbook) so the header identifies the install's
	 * country automatically. Flags are inline SVG (crisp, no image request).
	 * Extend $flags when launching a new country — see
	 * prepgro-engine/docs/country-theming.md.
	 *
	 * @return string
	 */
	private function country_chip() {
		$code = defined( 'PGE_COUNTRY' ) ? strtolower( (string) PGE_COUNTRY ) : '';

		/**
		 * Filter the country code used for the header chip.
		 *
		 * @param string $code Two-letter country code.
		 */
		$code = apply_filters( 'pgt_header_country_code', $code );

		$flags = array(
			'us' => array(
				'label' => 'USA',
				'svg'   => '<svg viewBox="0 0 60 42" width="22" height="15" aria-hidden="true">'
					. '<rect width="60" height="42" fill="#B22234"/>'
					. '<g fill="#F5F1E8"><rect y="6" width="60" height="6"/><rect y="18" width="60" height="6"/><rect y="30" width="60" height="6"/></g>'
					. '<rect width="27" height="24" fill="#3C3B6E"/>'
					. '<g fill="#F5F1E8"><circle cx="5.5" cy="5" r="1.7"/><circle cx="13.5" cy="5" r="1.7"/><circle cx="21.5" cy="5" r="1.7"/>'
					. '<circle cx="9.5" cy="11.5" r="1.7"/><circle cx="17.5" cy="11.5" r="1.7"/>'
					. '<circle cx="5.5" cy="18" r="1.7"/><circle cx="13.5" cy="18" r="1.7"/><circle cx="21.5" cy="18" r="1.7"/></g>'
					. '</svg>',
			),
			'ca' => array(
				'label' => 'Canada',
				'svg'   => '<svg viewBox="0 0 60 42" width="22" height="15" aria-hidden="true">'
					. '<rect width="60" height="42" fill="#F5F1E8"/>'
					. '<rect width="15" height="42" fill="#D80621"/><rect x="45" width="15" height="42" fill="#D80621"/>'
					. '<path d="M30 8 L32 15 L38 13 L34 19 L40 21 L33 23 L34 30 L30 26 L26 30 L27 23 L20 21 L26 19 L22 13 L28 15 Z" fill="#D80621"/>'
					. '</svg>',
			),
			'in' => array(
				'label' => 'India',
				'svg'   => '<svg viewBox="0 0 60 42" width="22" height="15" aria-hidden="true">'
					. '<rect width="60" height="14" fill="#FF9933"/><rect y="14" width="60" height="14" fill="#FFFFFF"/><rect y="28" width="60" height="14" fill="#138808"/>'
					. '<circle cx="30" cy="21" r="5.5" fill="none" stroke="#000080" stroke-width="1.4"/>'
					. '</svg>',
			),
		);

		if ( ! isset( $flags[ $code ] ) ) {
			return '';
		}

		return '<span class="pgt-region-chip" title="' . esc_attr( sprintf( __( 'PrepGro %s edition', 'prepgro-theme' ), $flags[ $code ]['label'] ) ) . '">'
			. $flags[ $code ]['svg']
			. '<span>' . esc_html( $flags[ $code ]['label'] ) . '</span>'
			. '</span>';
	}

	/**
	 * Default header logo — the prepGro brand-kit chip mark + wordmark,
	 * bundled directly into the theme (see prepgro-logo-kit-v34-production.html
	 * for the source spec: chip gradient #1A4FC4→#2D7EF5→#5299FF, wordmark
	 * "prep" in ink / "Gro" in brand-blue, Outfit typeface). Real text in the
	 * DOM (not a rasterized image) so it stays crisp and accessible. Used
	 * only when the site has no custom_logo set via Appearance > Editor, so
	 * setting one there always takes precedence.
	 *
	 * @return string
	 */
	private function brand_kit_logo() {
		return '<span class="pgt-brandlogo">'
			. '<svg class="pgt-brandlogo__chip" width="34" height="34" viewBox="0 0 92 92" aria-hidden="true">'
			. '<defs><linearGradient id="pgtLogoChipGrad" x1="0" y1="0" x2="1" y2="1">'
			. '<stop offset="0%" stop-color="#1A4FC4"/><stop offset="52%" stop-color="#2D7EF5"/><stop offset="100%" stop-color="#5299FF"/>'
			. '</linearGradient></defs>'
			. '<rect x="6" y="6" width="80" height="80" rx="16" fill="url(#pgtLogoChipGrad)"/>'
			. '<path d="M74 23 L74 43 L67 36 L33 70 L17 54 L23 48 L33 58 L61 30 L54 23 Z" fill="#FFFFFF" stroke="#FFFFFF" stroke-width="2" stroke-linejoin="round"/>'
			. '</svg>'
			. '<span class="pgt-brandlogo__copy">'
			. '<span class="pgt-brandlogo__word"><span class="pgt-brandlogo__prep">prep</span><span class="pgt-brandlogo__gro">Gro</span></span>'
			. '<span class="pgt-brandlogo__tagline">' . esc_html__( 'Evaluate. Elevate. Excel.', 'prepgro-theme' ) . '</span>'
			. '</span>'
			. '</span>';
	}

	/**
	 * Auth buttons — dashboard/logout when logged in; log in / get started when out.
	 *
	 * @return string
	 */
	private function auth_buttons() {
		ob_start();
		if ( is_user_logged_in() ) {
			$dash = home_url( '/my-dashboard/' );
			printf(
				'<a class="pgt-btn pgt-btn--ghost" href="%1$s">%2$s</a>',
				esc_url( $dash ),
				esc_html__( 'Dashboard', 'prepgro-theme' )
			);
			printf(
				'<a class="pgt-btn pgt-btn--muted" href="%1$s">%2$s</a>',
				esc_url( wp_logout_url( home_url( '/' ) ) ),
				esc_html__( 'Log out', 'prepgro-theme' )
			);
		} else {
			printf(
				'<a class="pgt-btn pgt-btn--ghost" href="%1$s">%2$s</a>',
				esc_url( wp_login_url( home_url( $_SERVER['REQUEST_URI'] ?? '/' ) ) ),
				esc_html__( 'Sign in', 'prepgro-theme' )
			);
			printf(
				'<a class="pgt-btn pgt-btn--primary" href="%1$s">%2$s</a>',
				esc_url( home_url( '/get-started/' ) ),
				esc_html__( 'Start free readiness check', 'prepgro-theme' )
			);
		}
		return (string) ob_get_clean();
	}
}
